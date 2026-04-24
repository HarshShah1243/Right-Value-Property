<?php
// --- Error Handling ---
// Log errors to a file instead of displaying them. This prevents breaking JSON responses.
// A file named 'error_log' will be created in this 'api' directory.
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log');

// --- Admin Credentials ---
define('ADMIN_USERNAME', 'AasStar');

// IMPORTANT: The default password is 'password'.
// You should generate a new hash for your own secure password.
// See the setup guide for instructions.
define('ADMIN_PASSWORD_HASH', '$2y$10$MzMciEgqS.yZPcChGYiB3e35b2s.zJpuGys926RcuaP114PamNsq2');

// --- Directory Configuration ---
// __DIR__ is the directory of the current file (api)
define('ROOT_DIR', dirname(__DIR__)); // This should resolve to /public_html
define('DATA_DIR', ROOT_DIR . '/data');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');

// This defines the public-facing URL path to your uploads folder.
// It's relative from the root of your domain.
define('UPLOADS_URL', 'uploads'); 

// --- Directory & File Checks ---
// Ensure data and uploads directories exist and are writable
if (!is_dir(DATA_DIR)) {
    mkdir(DATA_DIR, 0755, true);
}
if (!is_writable(DATA_DIR)) {
    error_log('Configuration Error: The data directory is not writable. Please check permissions.');
}

if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}
if (!is_writable(UPLOADS_DIR)) {
    error_log('Configuration Error: The uploads directory is not writable. Please check permissions.');
}

?>