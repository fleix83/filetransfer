<?php
// File-Based Cross-Device Sharing System Configuration

// Base paths
define('BASE_PATH', dirname(__FILE__) . '/');
define('SESSIONS_PATH', BASE_PATH . 'sessions/');
define('TEMP_PATH', BASE_PATH . 'temp/');

// File limits and restrictions
define('MAX_FILE_SIZE', 40 * 1024 * 1024); // 40MB
define('SESSION_LIFETIME', 30 * 24 * 60 * 60); // 30 days
define('CLEANUP_LOG', BASE_PATH . 'cleanup.log');

// Allowed file extensions
$allowedExtensions = [
    'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
    'txt', 'rtf', 'csv',
    'jpg', 'jpeg', 'png', 'gif', 'bmp',
    'zip', 'rar', '7z',
    'mp4', 'avi', 'mov', 'mp3', 'wav'
];

// Blocked extensions for security
$blockedExtensions = [
    'exe', 'bat', 'sh', 'cmd', 'com', 'scr', 'vbs', 'js',
    'php', 'asp', 'jsp', 'pl', 'py', 'rb'
];

// Allowed MIME types
$allowedMimeTypes = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'text/rtf',
    'text/csv',
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/bmp',
    'application/zip',
    'application/x-rar-compressed',
    'application/x-7z-compressed',
    'video/mp4',
    'video/avi',
    'video/quicktime',
    'audio/mpeg',
    'audio/wav'
];

// System settings
ini_set('upload_max_filesize', '40M');
ini_set('post_max_size', '45M');
ini_set('max_execution_time', 300);
ini_set('memory_limit', '256M');
ini_set('max_input_time', 300);

// Ensure sessions directory exists and is writable
if (!is_dir(SESSIONS_PATH)) {
    mkdir(SESSIONS_PATH, 0755, true);
}

if (!is_dir(TEMP_PATH)) {
    mkdir(TEMP_PATH, 0755, true);
}
?>
