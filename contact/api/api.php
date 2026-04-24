<?php
require_once 'config.php';
session_start();

header('Content-Type: application/json');

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
    $file_path = DATA_DIR . '/' . basename($collection) . '.json';
    $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($file_path, $json_data, LOCK_EX);
}

$method = $_SERVER['REQUEST_METHOD'];
$collection = $_GET['collection'] ?? null;

if (!$collection) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Collection not specified.']);
    exit;
}

$collection = preg_replace('/[^a-zA-Z0-9_-]/', '', $collection);

$single_item_collections = ['settings', 'page_home', 'page_about', 'page_contact', 'page_finance', 'page_legal'];
$is_single_item = in_array($collection, $single_item_collections);

switch ($method) {
    case 'GET':
        $id = $_GET['id'] ?? null;
        $data = get_data($collection, $is_single_item);

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
            echo json_encode($data);
        }
        break;

    case 'POST':
        require_admin();
        $item_data = $_POST;

        // Handle file uploads
        if (!empty($_FILES)) {
            $current_data = (array) get_data($collection, $is_single_item);
            if (!$is_single_item && isset($item_data['id'])) {
                $existing_item_array = array_filter($current_data, fn($d) => isset($d['id']) && $d['id'] === $item_data['id']);
                $current_data = current($existing_item_array) ?: [];
            }

            foreach ($_FILES as $key => $file) {
                if ($file['error'] === UPLOAD_ERR_OK) {
                    // Delete old file if it exists
                    if (isset($current_data[$key]) && strpos($current_data[$key], UPLOADS_URL) === 0) {
                        $old_file_path = ROOT_DIR . '/' . $current_data[$key];
                        if (file_exists($old_file_path)) @unlink($old_file_path);
                    }
                    $file_name = time() . '_' . basename($file['name']);
                    $target_path = UPLOADS_DIR . '/' . $file_name;
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        $item_data[$key] = UPLOADS_URL . '/' . $file_name;
                    }
                } else {
                    $url_key = $key . '_url';
                    if (isset($item_data[$url_key])) {
                        $item_data[$key] = $item_data[$url_key];
                    }
                }
                unset($item_data[$key . '_url']);
            }
        }

        if ($is_single_item) {
            $current_data = (array) get_data($collection, true);
            $new_data = array_merge($current_data, $item_data);
            save_data($collection, $new_data);
            echo json_encode(['success' => true, 'data' => $new_data]);
            break;
        }
        
        $id = $item_data['id'] ?? null;
        unset($item_data['id']);

        $data = get_data($collection);
        if ($id) {
            $found = false;
            foreach ($data as $key => &$value) {
                if (isset($value['id']) && $value['id'] === $id) {
                    $value = array_merge($value, $item_data);
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                 $item_data['id'] = $id;
                 $data[] = $item_data;
            }
        } else {
            $item_data['id'] = uniqid();
            $data[] = $item_data;
        }

        save_data($collection, array_values($data));
        echo json_encode(['success' => true, 'data' => end($data)]);
        break;

    case 'DELETE':
        require_admin();
        $id = $_GET['id'] ?? null;
        if (!$id || $is_single_item) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid operation.']);
            exit;
        }

        $data = get_data($collection);
        $item_to_delete = null;
        $data = array_filter($data, function($item) use ($id, &$item_to_delete) {
            if (isset($item['id']) && $item['id'] === $id) {
                $item_to_delete = $item;
                return false;
            }
            return true;
        });

        if ($item_to_delete) {
            foreach ($item_to_delete as $value) {
                if (is_string($value) && strpos($value, UPLOADS_URL) === 0) {
                    $file_path = ROOT_DIR . '/' . $value;
                    if (file_exists($file_path)) @unlink($file_path);
                }
            }
        }

        save_data($collection, array_values($data));
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not supported.']);
        break;
}
?>
