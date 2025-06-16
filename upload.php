<?php
// File Upload Handler

require_once 'functions.php';

// Set JSON response headers
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get session token
$sessionToken = $_POST['session_token'] ?? '';
if (empty($sessionToken)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Session token required']);
    exit;
}

// Validate session
$session = getSession($sessionToken);
if (!$session || $session['status'] !== 'active') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error = 'No file uploaded';
    if (isset($_FILES['file']['error'])) {
        switch ($_FILES['file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error = 'File too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error = 'File upload incomplete';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error = 'No file selected';
                break;
            default:
                $error = 'Upload failed - Error code: ' . $_FILES['file']['error'];
        }
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $error]);
    exit;
}

$uploadedFile = $_FILES['file'];
$uploadedBy = $_POST['uploaded_by'] ?? 'admin';

try {
    $fileName = uploadFile($sessionToken, $uploadedFile, $uploadedBy);
    echo json_encode([
        'success' => true, 
        'message' => 'File uploaded successfully',
        'filename' => $fileName,
        'original_name' => $uploadedFile['name']
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

