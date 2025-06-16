<?php
// Secure File Download Handler

require_once 'functions.php';

// Get parameters
$sessionToken = $_GET['token'] ?? '';
$fileName = $_GET['file'] ?? '';

if (empty($sessionToken) || empty($fileName)) {
    http_response_code(400);
    die('Missing required parameters');
}

// Validate session
$session = getSession($sessionToken);
if (!$session || $session['status'] !== 'active') {
    http_response_code(403);
    die('Access denied - invalid or expired session');
}

// Sanitize filename to prevent directory traversal
$fileName = basename($fileName);
$filePath = SESSIONS_PATH . $sessionToken . '/' . $fileName;

if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found');
}

// Get file metadata
$files = getSessionFiles($sessionToken);
if (!isset($files[$fileName])) {
    http_response_code(404);
    die('File metadata not found');
}

$fileInfo = $files[$fileName];

// Increment download counter
incrementDownloadCount($sessionToken, $fileName);

// Update session activity
updateSessionActivity($sessionToken);

// Set headers for file download
header('Content-Type: ' . $fileInfo['mime_type']);
header('Content-Disposition: attachment; filename="' . $fileInfo['original_name'] . '"');
header('Content-Length: ' . $fileInfo['file_size']);
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// Output file
readfile($filePath);
exit;
?>
