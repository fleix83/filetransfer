<?php
// Core Session Management Functions

require_once 'config.php';

/**
 * Create a new session
 * @param string $customerName
 * @param string $notes
 * @return string Session token
 * @throws Exception
 */
function createSession($customerName, $notes = '') {
    // Generate unique session token
    $token = 'SES-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
    $sessionDir = SESSIONS_PATH . $token;
    
    // Debug: Check if sessions path exists and is writable
    if (!is_dir(SESSIONS_PATH)) {
        throw new Exception("Sessions directory does not exist: " . SESSIONS_PATH);
    }
    
    if (!is_writable(SESSIONS_PATH)) {
        throw new Exception("Sessions directory is not writable: " . SESSIONS_PATH . " (permissions: " . substr(sprintf('%o', fileperms(SESSIONS_PATH)), -4) . ")");
    }
    
    // Create session directory
    if (!mkdir($sessionDir, 0777, true)) {
        $error = error_get_last();
        throw new Exception("Could not create session directory: " . $sessionDir . " - " . ($error['message'] ?? 'Unknown error'));
    }
    
    // Create session metadata
    $sessionData = [
        'token' => $token,
        'customer_name' => sanitizeInput($customerName),
        'created_at' => date('c'),
        'expires_at' => date('c', time() + SESSION_LIFETIME),
        'status' => 'active',
        'notes' => sanitizeInput($notes),
        'qr_code_generated' => false,
        'last_activity' => date('c')
    ];
    
    // Write session metadata
    $sessionFile = $sessionDir . '/session.json';
    if (file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT)) === false) {
        // Clean up directory if file creation failed
        rmdir($sessionDir);
        throw new Exception("Could not create session metadata");
    }
    
    // Initialize empty files tracking
    $filesFile = $sessionDir . '/files.json';
    if (file_put_contents($filesFile, json_encode([], JSON_PRETTY_PRINT)) === false) {
        // Clean up if files.json creation failed
        unlink($sessionFile);
        rmdir($sessionDir);
        throw new Exception("Could not initialize files tracking");
    }
    
    return $token;
}

/**
 * Get session data by token
 * @param string $token
 * @return array|null Session data or null if not found
 */
function getSession($token) {
    // Validate token format
    if (!preg_match('/^SES-\d{8}-[A-Z0-9]{6}$/', $token)) {
        return null;
    }
    
    $sessionFile = SESSIONS_PATH . $token . '/session.json';
    
    if (!file_exists($sessionFile)) {
        return null;
    }
    
    $session = json_decode(file_get_contents($sessionFile), true);
    
    if (!$session) {
        return null;
    }
    
    // Check expiration
    if (strtotime($session['expires_at']) < time()) {
        $session['status'] = 'expired';
    }
    
    return $session;
}

/**
 * Update session activity timestamp
 * @param string $sessionToken
 * @return bool Success status
 */
function updateSessionActivity($sessionToken) {
    $sessionFile = SESSIONS_PATH . $sessionToken . '/session.json';
    
    if (!file_exists($sessionFile)) {
        return false;
    }
    
    $session = json_decode(file_get_contents($sessionFile), true);
    if (!$session) {
        return false;
    }
    
    $session['last_activity'] = date('c');
    
    return file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT)) !== false;
}

/**
 * Get all files for a session
 * @param string $sessionToken
 * @return array Files metadata
 */
function getSessionFiles($sessionToken) {
    $filesJsonPath = SESSIONS_PATH . $sessionToken . '/files.json';
    
    if (file_exists($filesJsonPath)) {
        $files = json_decode(file_get_contents($filesJsonPath), true);
        return $files ?: [];
    }
    
    return [];
}

/**
 * Sanitize user input
 * @param string $input
 * @return string Cleaned input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize filename for safe storage
 * @param string $filename
 * @return string Safe filename
 */
function sanitizeFilename($filename) {
    // Remove dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    $filename = preg_replace('/_{2,}/', '_', $filename);
    return trim($filename, '_');
}

/**
 * Upload file to session
 * @param string $sessionToken
 * @param array $uploadedFile $_FILES array element
 * @param string $uploadedBy Who uploaded the file (admin/customer)
 * @return string Final filename
 * @throws Exception
 */
function uploadFile($sessionToken, $uploadedFile, $uploadedBy) {
    global $allowedExtensions, $blockedExtensions, $allowedMimeTypes;
    
    $session = getSession($sessionToken);
    if (!$session || $session['status'] !== 'active') {
        throw new Exception("Invalid or expired session");
    }
    
    $sessionDir = SESSIONS_PATH . $sessionToken;
    
    // Validate file size (from config.php MAX_FILE_SIZE)
    if ($uploadedFile['size'] > MAX_FILE_SIZE) {
        throw new Exception("File too large. Maximum size is " . formatFileSize(MAX_FILE_SIZE));
    }
    
    // Get file extension
    $originalName = $uploadedFile['name'];
    $pathInfo = pathinfo($originalName);
    $extension = strtolower($pathInfo['extension'] ?? '');
    
    // Validate file extension
    if (!empty($blockedExtensions) && in_array($extension, $blockedExtensions)) {
        throw new Exception("File type '$extension' is not allowed for security reasons");
    }
    
    if (!empty($allowedExtensions) && !in_array($extension, $allowedExtensions)) {
        throw new Exception("File type '$extension' is not allowed. Allowed types: " . implode(', ', $allowedExtensions));
    }
    
    // Validate MIME type
    $mimeType = $uploadedFile['type'];
    if (!empty($allowedMimeTypes) && !in_array($mimeType, $allowedMimeTypes)) {
        // Try to detect MIME type using fileinfo
        if (function_exists('finfo_file')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($detectedMimeType, $allowedMimeTypes)) {
                throw new Exception("File type not allowed. Detected type: $detectedMimeType");
            }
            $mimeType = $detectedMimeType;
        }
    }
    
    // Sanitize and create unique filename
    $baseName = sanitizeFilename($pathInfo['filename']);
    $fileName = $baseName . '.' . $extension;
    
    // Handle filename conflicts
    $counter = 1;
    while (file_exists("$sessionDir/$fileName")) {
        $fileName = $baseName . "_{$counter}." . $extension;
        $counter++;
    }
    
    $targetPath = "$sessionDir/$fileName";
    
    // Move uploaded file
    if (!move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save uploaded file");
    }
    
    // Update file metadata
    updateFileMetadata($sessionToken, $fileName, $uploadedBy, $uploadedFile, $mimeType);
    updateSessionActivity($sessionToken);
    
    return $fileName;
}

/**
 * Update file metadata in files.json
 * @param string $sessionToken
 * @param string $fileName
 * @param string $uploadedBy
 * @param array $fileInfo
 * @param string $mimeType
 */
function updateFileMetadata($sessionToken, $fileName, $uploadedBy, $fileInfo, $mimeType = null) {
    $filesJsonPath = SESSIONS_PATH . $sessionToken . '/files.json';
    $files = json_decode(file_get_contents($filesJsonPath), true) ?: [];
    
    $files[$fileName] = [
        'uploaded_by' => $uploadedBy,
        'uploaded_at' => date('c'),
        'file_size' => $fileInfo['size'],
        'mime_type' => $mimeType ?: $fileInfo['type'],
        'original_name' => $fileInfo['name'],
        'download_count' => 0
    ];
    
    file_put_contents($filesJsonPath, json_encode($files, JSON_PRETTY_PRINT));
}

/**
 * Get all active sessions (for admin dashboard)
 * @return array List of sessions
 */
function getAllSessions() {
    $sessions = [];
    $sessionDirs = glob(SESSIONS_PATH . 'SES-*');
    
    foreach ($sessionDirs as $sessionDir) {
        $token = basename($sessionDir);
        $session = getSession($token);
        
        if ($session) {
            $sessions[] = $session;
        }
    }
    
    // Sort by creation date (newest first)
    usort($sessions, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $sessions;
}

/**
 * Increment download count for a file
 * @param string $sessionToken
 * @param string $fileName
 */
function incrementDownloadCount($sessionToken, $fileName) {
    $filesJsonPath = SESSIONS_PATH . $sessionToken . '/files.json';
    $files = json_decode(file_get_contents($filesJsonPath), true) ?: [];
    
    if (isset($files[$fileName])) {
        $files[$fileName]['download_count']++;
        file_put_contents($filesJsonPath, json_encode($files, JSON_PRETTY_PRINT));
    }
}

/**
 * Delete a file from session
 * @param string $sessionToken
 * @param string $fileName
 * @return bool Success status
 */
function deleteFile($sessionToken, $fileName) {
    $session = getSession($sessionToken);
    if (!$session || $session['status'] !== 'active') {
        return false;
    }
    
    $fileName = basename($fileName); // Prevent directory traversal
    $filePath = SESSIONS_PATH . $sessionToken . '/' . $fileName;
    
    // Remove physical file
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Remove from metadata
    $filesJsonPath = SESSIONS_PATH . $sessionToken . '/files.json';
    $files = json_decode(file_get_contents($filesJsonPath), true) ?: [];
    
    if (isset($files[$fileName])) {
        unset($files[$fileName]);
        file_put_contents($filesJsonPath, json_encode($files, JSON_PRETTY_PRINT));
    }
    
    updateSessionActivity($sessionToken);
    return true;
}

/**
 * Format file size for display
 * @param int $size Size in bytes
 * @return string Formatted size
 */
function formatFileSize($size) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $unitIndex = 0;
    
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    
    return round($size, 2) . ' ' . $units[$unitIndex];
}

/**
 * Generate session URL
 * @param string $token
 * @return string Full URL to session
 */
function getSessionUrl($token) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['REQUEST_URI']);
    
    return $protocol . '://' . $host . $path . '/index.php?token=' . $token;
}
?>
