<?php
// Customer Interface for File Transfer System

require_once 'functions.php';

// Get session token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: admin.php');
    exit;
}

// Validate session
$session = getSession($token);
if (!$session) {
    showCustomerError('Invalid Session', 'The session link you used is not valid.');
}

if ($session['status'] === 'expired') {
    showCustomerError('Session Expired', 'This file sharing session has expired. Please contact the sender for a new link.');
}

// Get session files
$files = getSessionFiles($token);

// Update session activity
updateSessionActivity($token);

/**
 * Show customer error page
 */
function showCustomerError($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - File Exchange</title>
        <style>
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .error-container {
                max-width: 400px;
                background: white;
                padding: 40px 30px;
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                text-align: center;
            }
            
            .error-icon {
                font-size: 64px;
                margin-bottom: 20px;
            }
            
            h1 {
                color: #e74c3c;
                margin-bottom: 15px;
                font-size: 24px;
                font-weight: 600;
            }
            
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 0;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">❌</div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Exchange - <?php echo htmlspecialchars($session['customer_name']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 300;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .session-info {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .session-info h2 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 8px;
        }
        
        .session-info p {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        /* Upload Area */
        .upload-section {
            margin-bottom: 30px;
        }
        
        .upload-area {
            border: 2px dashed #27ae60;
            border-radius: 15px;
            padding: 40px 20px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: #219a52;
            background: #e8f5e8;
            transform: translateY(-2px);
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .upload-text {
            color: #27ae60;
            font-weight: 500;
            margin-bottom: 10px;
        }
        
        .upload-hint {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .btn {
            background: #27ae60;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-top: 15px;
        }
        
        .btn:hover {
            background: #219a52;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Files List */
        .files-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 18px;
        }
        
        .file-item {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .file-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        
        .file-icon {
            font-size: 32px;
            margin-right: 15px;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .file-meta {
            color: #7f8c8d;
            font-size: 12px;
        }
        
        .download-icon {
            color: #3498db;
            font-size: 24px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #7f8c8d;
        }
        
        .empty-icon {
            font-size: 64px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Upload Progress */
        .upload-progress {
            margin-top: 15px;
            height: 6px;
            background: #ecf0f1;
            border-radius: 3px;
            overflow: hidden;
            display: none;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            width: 0%;
            transition: width 0.3s;
        }
        
        .upload-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 10px;
            text-align: center;
            font-size: 14px;
            display: none;
        }
        
        .upload-status.success {
            background: #d4edda;
            color: #155724;
        }
        
        .upload-status.error {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .card {
                padding: 20px;
                border-radius: 15px;
            }
            
            .file-item {
                padding: 15px;
            }
            
            .upload-area {
                padding: 30px 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 File Exchange</h1>
            <p>Secure file sharing</p>
        </div>
        
        <div class="card">
            <div class="session-info">
                <h2><?php echo htmlspecialchars($session['customer_name']); ?></h2>
                <p>
                    <?php if ($session['notes']): ?>
                        <?php echo htmlspecialchars($session['notes']); ?><br>
                    <?php endif; ?>
                    Session expires: <?php echo date('M j, Y', strtotime($session['expires_at'])); ?>
                </p>
            </div>
            
            <!-- Upload Section -->
            <div class="upload-section">
                <div class="upload-area" id="uploadArea">
                    <div class="upload-icon">📎</div>
                    <div class="upload-text">Tap to upload files</div>
                    <div class="upload-hint">or drag and drop here</div>
                    <input type="file" id="fileInput" multiple style="display: none;" accept="<?php echo implode(',', array_map(function($ext) { return '.' . $ext; }, $allowedExtensions)); ?>">
                    <button type="button" class="btn" onclick="document.getElementById('fileInput').click();">Choose Files</button>
                </div>
                <div class="upload-progress" id="uploadProgress">
                    <div class="upload-progress-bar" id="uploadProgressBar"></div>
                </div>
                <div class="upload-status" id="uploadStatus"></div>
            </div>
            
            <!-- Files Section -->
            <div class="files-section">
                <h3>📋 Available Files (<?php echo count($files); ?>)</h3>
                
                <div id="filesList">
                    <?php if (empty($files)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📄</div>
                            <p>No files available yet.<br>Check back later or upload your files above.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($files as $fileName => $fileInfo): ?>
                            <div class="file-item" onclick="downloadFile('<?php echo urlencode($fileName); ?>')">
                                <div class="file-icon"><?php echo getFileIcon($fileInfo['mime_type']); ?></div>
                                <div class="file-info">
                                    <div class="file-name"><?php echo htmlspecialchars($fileInfo['original_name']); ?></div>
                                    <div class="file-meta">
                                        <?php echo formatFileSize($fileInfo['file_size']); ?> • 
                                        <?php echo date('M j, g:i A', strtotime($fileInfo['uploaded_at'])); ?>
                                    </div>
                                </div>
                                <div class="download-icon">⬇️</div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // File upload functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadProgressBar = document.getElementById('uploadProgressBar');
            const uploadStatus = document.getElementById('uploadStatus');
            const filesList = document.getElementById('filesList');
            
            // Drag and drop functionality
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    uploadFiles(files);
                }
            });
            
            // File input change
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    uploadFiles(e.target.files);
                }
            });
            
            // Upload files function
            function uploadFiles(files) {
                for (let i = 0; i < files.length; i++) {
                    uploadFile(files[i]);
                }
            }
            
            // Upload single file
            function uploadFile(file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('session_token', '<?php echo $token; ?>');
                formData.append('uploaded_by', 'customer');
                
                // Show progress
                uploadProgress.style.display = 'block';
                uploadProgressBar.style.width = '0%';
                uploadStatus.style.display = 'none';
                
                const xhr = new XMLHttpRequest();
                
                // Progress tracking
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        uploadProgressBar.style.width = percentComplete + '%';
                    }
                });
                
                // Handle response
                xhr.addEventListener('load', function() {
                    uploadProgress.style.display = 'none';
                    uploadStatus.style.display = 'block';
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                uploadStatus.className = 'upload-status success';
                                uploadStatus.textContent = '✅ ' + response.filename + ' uploaded successfully!';
                                
                                // Reload page after a delay to show new file
                                setTimeout(function() {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                uploadStatus.className = 'upload-status error';
                                uploadStatus.textContent = '❌ Upload failed: ' + response.error;
                            }
                        } catch (e) {
                            uploadStatus.className = 'upload-status error';
                            uploadStatus.textContent = '❌ Upload failed: Invalid response';
                        }
                    } else {
                        uploadStatus.className = 'upload-status error';
                        uploadStatus.textContent = '❌ Upload failed: Server error';
                    }
                });
                
                // Handle error
                xhr.addEventListener('error', function() {
                    uploadProgress.style.display = 'none';
                    uploadStatus.style.display = 'block';
                    uploadStatus.className = 'upload-status error';
                    uploadStatus.textContent = '❌ Upload failed: Network error';
                });
                
                // Send request
                xhr.open('POST', 'upload.php');
                xhr.send(formData);
            }
        });
        
        // Download file function
        function downloadFile(fileName) {
            window.location.href = 'download.php?token=<?php echo urlencode($token); ?>&file=' + fileName;
        }
    </script>
</body>
</html>

<?php
/**
 * Get file icon based on MIME type
 */
function getFileIcon($mimeType) {
    if (strpos($mimeType, 'image/') === 0) return '🖼️';
    if (strpos($mimeType, 'video/') === 0) return '🎥';
    if (strpos($mimeType, 'audio/') === 0) return '🎵';
    if (strpos($mimeType, 'application/pdf') === 0) return '📄';
    if (strpos($mimeType, 'application/zip') === 0) return '🗜️';
    if (strpos($mimeType, 'text/') === 0) return '📝';
    if (strpos($mimeType, 'application/msword') === 0 || 
        strpos($mimeType, 'application/vnd.openxmlformats-officedocument.wordprocessingml') === 0) return '📝';
    if (strpos($mimeType, 'application/vnd.ms-excel') === 0 || 
        strpos($mimeType, 'application/vnd.openxmlformats-officedocument.spreadsheetml') === 0) return '📊';
    if (strpos($mimeType, 'application/vnd.ms-powerpoint') === 0 || 
        strpos($mimeType, 'application/vnd.openxmlformats-officedocument.presentationml') === 0) return '📈';
    
    return '📎';
}
?>
