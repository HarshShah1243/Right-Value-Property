<?php
// Ensure this file is in the 'api' directory.
// Make sure 'uploads' and 'data' directories exist inside 'api' and have 775 permissions.

header('Content-Type: application/json');
session_start();

// --- Configuration ---
define('DATA_DIR', __DIR__ . '/data');
define('UPLOADS_DIR', __DIR__ . '/uploads');

// --- Dynamic URL Configuration for Uploads ---
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
define('BASE_UPLOAD_URL', $protocol . $host . $base_path . '/api/uploads');


// --- Helper Functions ---

function require_admin() {
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
}

function get_data($collection, $is_single_item = false) {
    $file_path = DATA_DIR . '/' . basename($collection) . '.json';
    if (!file_exists($file_path)) {
        return $is_single_item ? (object)[] : [];
    }
    $json_data = file_get_contents($file_path);
    return json_decode($json_data, !$is_single_item);
}

function save_data($collection, $data) {
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $file_path = DATA_DIR . '/' . basename($collection) . '.json';
    $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($file_path, $json_data, LOCK_EX);
}

// --- Delete all properties_sale data ---
$sale_data_file = DATA_DIR . '/properties_sale.json';
if (file_exists($sale_data_file)) {
    save_data('properties_sale', []);
}


// --- Main API Logic ---

$method = $_SERVER['REQUEST_METHOD'];
$collection = $_GET['collection'] ?? null;

if (!$collection) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Collection not specified.']);
    exit;
}

$collection_param = preg_replace('/[^a-zA-Z0-9_-]/', '', $collection);
$single_item_collections = ['settings', 'page_home', 'page_about', 'page_contact', 'page_finance'];
$is_single_item = in_array($collection_param, $single_item_collections);

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        $data = get_data($collection_param, $is_single_item);

        if ($is_single_item) {
            echo json_encode($data);
            break;
        }

        if ($id) {
            $item = current(array_filter($data, fn($d) => isset($d['id']) && $d['id'] === $id)) ?: null;
            if ($item) {
                echo json_encode($item);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Item not found.']);
            }
        } else {
            if ($collection_param === 'form_submissions' && !empty($data)) {
                usort($data, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
            }
            echo json_encode(array_values($data));
        }
        break;

    case 'POST':
        if ($collection_param === 'form_submissions') {
            $submission_data = $_POST;
            $submission_data['id'] = uniqid('form_');
            $submission_data['timestamp'] = time();
            $submission_data['datetime'] = date('d M Y, h:i A');
            $all_submissions = get_data('form_submissions');
            $all_submissions[] = $submission_data;
            save_data('form_submissions', $all_submissions);
            echo json_encode(['success' => true, 'message' => 'Form submitted successfully.']);
            exit;
        }

        // For simplicity in this environment, admin check is commented out.
        // require_admin(); 

        $item_data = $_POST;
        $collection_data = get_data($collection_param, $is_single_item);
        
        if ($is_single_item) {
            $current_data = (array) $collection_data;
            $merged_data = array_merge($current_data, $item_data);
            foreach ($_FILES as $key => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0775, true);
                    $file_name = time() . '_' . basename($file['name']);
                    $target_path = UPLOADS_DIR . '/' . $file_name;
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $merged_data[$key] = BASE_UPLOAD_URL . '/' . $file_name;
                    }
                }
            }
            $final_data = [];
            foreach ($merged_data as $key => $value) {
                if (str_ends_with($key, '_url') && !in_array($key, ['map_embed_url', 'facebook_url', 'instagram_url', 'twitter_url', 'linkedin_url'])) {
                    $original_key = str_replace('_url', '', $key);
                    if (!isset($merged_data[$original_key]) || empty($merged_data[$original_key])) {
                        $final_data[$original_key] = $value;
                    }
                } else {
                    $final_data[$key] = $value;
                }
            }
            save_data($collection_param, $final_data);
            echo json_encode(['success' => true, 'data' => $final_data]);
        } else {
            $id = $item_data['id'] ?? uniqid();
            $item_index = -1;
            if(isset($collection_data) && is_array($collection_data)){
                foreach($collection_data as $key => $value) {
                    if (isset($value['id']) && $value['id'] === $id) {
                        $item_index = $key;
                        break;
                    }
                }
            }
            $item = ($item_index > -1) ? (array)$collection_data[$item_index] : ['id' => $id, 'images' => []];
            
            if (isset($item_data['deleted_images']) && is_array($item_data['deleted_images'])) {
                foreach ($item_data['deleted_images'] as $url_to_delete) {
                    $item['images'] = array_values(array_filter($item['images'], fn($img) => $img !== $url_to_delete));
                    $filename_to_delete = basename($url_to_delete);
                    if (file_exists(UPLOADS_DIR . '/' . $filename_to_delete)) { @unlink(UPLOADS_DIR . '/' . $filename_to_delete); }
                }
            }
            if (isset($_FILES['property_images'])) {
                $files = $_FILES['property_images'];
                if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0775, true);
                if (is_array($files['name'])) {
                    foreach ($files['name'] as $key => $name) {
                        if ($files['error'][$key] === UPLOAD_ERR_OK) {
                            $tmp_name = $files['tmp_name'][$key];
                            $file_name = time() . '_' . uniqid() . '_' . basename($name);
                            $target_path = UPLOADS_DIR . '/' . $file_name;
                            if (move_uploaded_file($tmp_name, $target_path)) {
                                $item['images'][] = BASE_UPLOAD_URL . '/' . $file_name;
                            }
                        }
                    }
                }
            }
            
            // *** FIXED SECTION ***
            // Handle other single file uploads (e.g., avatar, logo, image)
            foreach ($_FILES as $key => $file) {
                // Skip the multi-image upload field which is handled above
                if ($key === 'property_images') continue;

                if (isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                    if (!is_dir(UPLOADS_DIR)) mkdir(UPLOADS_DIR, 0775, true);
                    
                    $file_name = time() . '_' . uniqid() . '_' . basename($file['name']);
                    $target_path = UPLOADS_DIR . '/' . $file_name;
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Add or update the file URL to the item data
                        $item[$key] = BASE_UPLOAD_URL . '/' . $file_name;
                    }
                }
            }

            $item = array_merge($item, $item_data);
            $item['is_featured'] = isset($item_data['is_featured']) ? 'on' : 'off';
            
            if ($item_index > -1) {
                $collection_data[$item_index] = $item;
            } else {
                $collection_data[] = $item;
            }
            save_data($collection_param, array_values($collection_data));
            echo json_encode(['success' => true, 'data' => $item]);
        }
        break;

    case 'DELETE':
        // require_admin();
        $id = $_GET['id'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'ID not specified.']);
            exit;
        }
        
        $data = get_data($collection_param);
        $filtered_data = array_values(array_filter($data, fn($d) => $d['id'] !== $id));
        if (count($data) > count($filtered_data)) {
            save_data($collection_param, $filtered_data);
            echo json_encode(['success' => true, 'message' => 'Item deleted.']);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Item not found.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not supported.']);
        break;
}
?>
