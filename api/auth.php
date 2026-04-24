<?php
require_once 'config.php';

session_start();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['is_admin'] = true;
        echo json_encode(['success' => true, 'message' => 'Login successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
    exit;
}

if ($action === 'logout') {
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully.']);
    exit;
}

if ($action === 'check_session') {
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
        echo json_encode(['isLoggedIn' => true]);
    } else {
        echo json_encode(['isLoggedIn' => false]);
    }
    exit;
}

// Default response for invalid actions
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid authentication action.']);

?>
