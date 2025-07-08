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
                font-family: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
                background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
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
                padding: 48px 32px;
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.1);
                text-align: center;
                border: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .error-icon {
                font-size: 56px;
                margin-bottom: 24px;
                opacity: 0.8;
            }
            
            h1 {
                color: #e53e3e;
                margin-bottom: 16px;
                font-size: 24px;
                font-weight: 500;
                font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
                letter-spacing: -0.01em;
            }
            
            p {
                color: #718096;
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
            font-family: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
            background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
            min-height: 100vh;
            padding: 20px;
            color: #2d3748;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 32px;
            color: white;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 500;
            margin-bottom: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            letter-spacing: -0.02em;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
        }
        
        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15), 0 1px 3px rgba(0,0,0,0.1);
            padding: 32px;
            margin-bottom: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .session-info {
            text-align: center;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .session-info h2 {
            color: #2d3748;
            font-size: 24px;
            margin-bottom: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            font-weight: 500;
            letter-spacing: -0.01em;
        }
        
        .session-info p {
            color: #718096;
            font-size: 14px;
            line-height: 1.5;
        }
        
        /* Upload Area - Anthropic Clean Style */
        .upload-section {
            margin-bottom: 32px;
        }
        
        .upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 48px 24px;
            text-align: center;
            background: #f7fafc;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .upload-area:hover,
        .upload-area.dragover {
            border-color: #3182ce;
            background: #ebf8ff;
            transform: translateY(-2px);
        }
        
        .upload-icon {
            font-size: 40px;
            margin-bottom: 16px;
            opacity: 0.6;
        }
        
        .upload-text {
            color: #3182ce;
            font-weight: 500;
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .upload-hint {
            color: #718096;
            font-size: 12px;
        }
        
        .btn {
            background: #3182ce;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
            margin-top: 16px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
        }
        
        .btn:hover {
            background: #2c5aa0;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(49, 130, 206, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        /* Files List - Anthropic Style */
        .files-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 18px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            font-weight: 500;
        }
        
        .file-item {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
            cursor: pointer;
            border: 1px solid #e2e8f0;
        }
        
        .file-item:hover {
            background: #ebf8ff;
            transform: translateX(4px);
            border-color: #3182ce;
        }
        
        .file-icon {
            font-size: 28px;
            margin-right: 16px;
            opacity: 0.8;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 4px;
            font-size: 15px;
        }
        
        .file-meta {
            color: #718096;
            font-size: 12px;
        }
        
        .download-icon {
            color: #3182ce;
            font-size: 20px;
            opacity: 0.7;
        }
        
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.4;
        }
        
        /* Upload Progress - Anthropic Style */
        .upload-progress {
            margin-top: 16px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            display: none;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3182ce, #2c5aa0);
            width: 0%;
            transition: width 0.3s;
        }
        
        .upload-status {
            margin-top: 12px;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 14px;
            display: none;
        }
        
        .upload-status.success {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }
        
        .upload-status.error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
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
