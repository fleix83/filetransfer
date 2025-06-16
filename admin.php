<?php
// Admin Dashboard for File Transfer System

require_once 'functions.php';

// Handle session creation
$message = '';
$messageType = '';
$selectedSession = null;

// Handle different actions
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_session') {
        $customerName = $_POST['customer_name'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($customerName)) {
            $message = 'Customer name is required.';
            $messageType = 'error';
        } else {
            try {
                $token = createSession($customerName, $notes);
                $sessionUrl = getSessionUrl($token);
                $message = "Session created successfully! Token: <strong>$token</strong><br>
                          Share this URL: <br><a href='$sessionUrl' target='_blank'>$sessionUrl</a>";
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error creating session: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($_POST['action'] === 'delete_file') {
        $sessionToken = $_POST['session_token'] ?? '';
        $fileName = $_POST['file_name'] ?? '';
        
        if ($sessionToken && $fileName) {
            if (deleteFile($sessionToken, $fileName)) {
                $message = 'File deleted successfully.';
                $messageType = 'success';
                $selectedSession = $sessionToken;
            } else {
                $message = 'Error deleting file.';
                $messageType = 'error';
            }
        }
    }
}

// Handle session selection for file management
if (isset($_GET['session'])) {
    $selectedSession = $_GET['session'];
}

// Get all sessions for display
$sessions = getAllSessions();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Transfer Admin Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .header h1 {
            text-align: center;
            font-size: 28px;
            font-weight: 300;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .btn {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-primary {
            background: #3498db;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .sessions-list {
            margin-top: 20px;
        }
        
        .session-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .session-info h4 {
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .session-meta {
            font-size: 14px;
            color: #666;
        }
        
        .session-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .session-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
        
        /* File Management Styles */
        .file-upload-area {
            border: 2px dashed #3498db;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            margin-bottom: 20px;
            transition: border-color 0.3s;
        }
        
        .file-upload-area:hover {
            border-color: #2980b9;
        }
        
        .file-upload-area.dragover {
            border-color: #2980b9;
            background: #e3f2fd;
        }
        
        .file-list {
            margin-top: 20px;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 8px;
            background: white;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 4px;
        }
        
        .file-meta {
            font-size: 12px;
            color: #666;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 4px;
        }
        
        .btn-download {
            background: #27ae60;
            color: white;
        }
        
        .btn-download:hover {
            background: #219a52;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
        }
        
        .upload-progress {
            margin-top: 10px;
            height: 4px;
            background: #ecf0f1;
            border-radius: 2px;
            overflow: hidden;
            display: none;
        }
        
        .upload-progress-bar {
            height: 100%;
            background: #3498db;
            width: 0%;
            transition: width 0.3s;
        }
        
        .session-selector {
            margin-bottom: 20px;
        }
        
        .session-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .session-tab {
            padding: 8px 16px;
            background: #ecf0f1;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .session-tab:hover {
            background: #d5dbdb;
        }
        
        .session-tab.active {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>File Transfer Admin Dashboard</h1>
        </div>
    </div>
    
    <div class="container">
        <div class="grid">
            <!-- Session Creation Form -->
            <div class="card">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Create New Session</h2>
                
                <?php if ($message): ?>
                    <div class="message <?php echo $messageType; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_session">
                    
                    <div class="form-group">
                        <label for="customer_name">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" 
                               placeholder="Enter customer or company name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes (Optional)</label>
                        <textarea id="notes" name="notes" 
                                  placeholder="Project details, purpose, or any additional notes..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Session</button>
                </form>
            </div>
            
            <!-- File Management or Sessions List -->
            <div class="card">
                <?php if ($selectedSession): ?>
                    <?php 
                    $session = getSession($selectedSession);
                    $files = getSessionFiles($selectedSession);
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="color: #2c3e50; margin: 0;">Manage Files - <?php echo htmlspecialchars($session['customer_name']); ?></h2>
                        <a href="admin.php" class="btn" style="background: #95a5a6; text-decoration: none;">← Back to Sessions</a>
                    </div>
                    
                    <!-- File Upload Area -->
                    <div class="file-upload-area" id="uploadArea">
                        <div id="uploadForm">
                            <div style="font-size: 48px; margin-bottom: 10px;">📁</div>
                            <p style="margin-bottom: 15px; color: #666;">Drag and drop files here or click to browse</p>
                            <input type="file" id="fileInput" multiple style="display: none;">
                            <button type="button" class="btn" onclick="document.getElementById('fileInput').click();">Choose Files</button>
                            <p style="margin-top: 10px; font-size: 12px; color: #999;">
                                Maximum file size: <?php echo formatFileSize(MAX_FILE_SIZE); ?>
                            </p>
                        </div>
                        <div class="upload-progress" id="uploadProgress">
                            <div class="upload-progress-bar" id="uploadProgressBar"></div>
                        </div>
                    </div>
                    
                    <!-- Files List -->
                    <div class="file-list" id="filesList">
                        <?php if (empty($files)): ?>
                            <p style="color: #666; text-align: center; padding: 20px;">
                                No files uploaded yet. Upload your first file above.
                            </p>
                        <?php else: ?>
                            <?php foreach ($files as $fileName => $fileInfo): ?>
                                <div class="file-item">
                                    <div class="file-info">
                                        <div class="file-name"><?php echo htmlspecialchars($fileInfo['original_name']); ?></div>
                                        <div class="file-meta">
                                            <?php echo formatFileSize($fileInfo['file_size']); ?> • 
                                            Uploaded <?php echo date('M j, Y g:i A', strtotime($fileInfo['uploaded_at'])); ?> • 
                                            By <?php echo htmlspecialchars($fileInfo['uploaded_by']); ?>
                                            <?php if ($fileInfo['download_count'] > 0): ?>
                                                • Downloaded <?php echo $fileInfo['download_count']; ?> time(s)
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="file-actions">
                                        <a href="download.php?token=<?php echo urlencode($selectedSession); ?>&file=<?php echo urlencode($fileName); ?>" 
                                           class="btn btn-small btn-download">Download</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_file">
                                            <input type="hidden" name="session_token" value="<?php echo htmlspecialchars($selectedSession); ?>">
                                            <input type="hidden" name="file_name" value="<?php echo htmlspecialchars($fileName); ?>">
                                            <button type="submit" class="btn btn-small btn-delete" 
                                                    onclick="return confirm('Are you sure you want to delete this file?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <h2 style="margin-bottom: 20px; color: #2c3e50;">Recent Sessions</h2>
                    
                    <?php if (empty($sessions)): ?>
                        <p style="color: #666; text-align: center; padding: 20px;">
                            No sessions created yet. Create your first session to get started.
                        </p>
                    <?php else: ?>
                        <div class="sessions-list">
                            <?php foreach (array_slice($sessions, 0, 10) as $session): ?>
                                <div class="session-item">
                                    <div class="session-info">
                                        <h4><?php echo htmlspecialchars($session['customer_name']); ?></h4>
                                        <div class="session-meta">
                                            <strong>Token:</strong> <?php echo $session['token']; ?><br>
                                            <strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?><br>
                                            <?php if ($session['notes']): ?>
                                                <strong>Notes:</strong> <?php echo htmlspecialchars($session['notes']); ?><br>
                                            <?php endif; ?>
                                            <strong>URL:</strong> <a href="<?php echo getSessionUrl($session['token']); ?>" target="_blank">
                                                <?php echo getSessionUrl($session['token']); ?>
                                            </a><br>
                                            <strong>Files:</strong> <?php echo count(getSessionFiles($session['token'])); ?> uploaded
                                        </div>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 8px;">
                                        <div class="session-status status-<?php echo $session['status']; ?>">
                                            <?php echo $session['status']; ?>
                                        </div>
                                        <a href="admin.php?session=<?php echo urlencode($session['token']); ?>" 
                                           class="btn btn-small" style="text-decoration: none; text-align: center;">Manage Files</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
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
            const filesList = document.getElementById('filesList');
            
            if (!uploadArea || !fileInput) return; // Only run on file management page
            
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
                formData.append('session_token', '<?php echo $selectedSession; ?>');
                formData.append('uploaded_by', 'admin');
                
                // Show progress
                uploadProgress.style.display = 'block';
                uploadProgressBar.style.width = '0%';
                
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
                    
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                // Reload page to show new file
                                window.location.reload();
                            } else {
                                alert('Upload failed: ' + response.error);
                            }
                        } catch (e) {
                            alert('Upload failed: Invalid response');
                        }
                    } else {
                        alert('Upload failed: Server error');
                    }
                });
                
                // Handle error
                xhr.addEventListener('error', function() {
                    uploadProgress.style.display = 'none';
                    alert('Upload failed: Network error');
                });
                
                // Send request
                xhr.open('POST', 'upload.php');
                xhr.send(formData);
            }
        });
    </script>
</body>
</html>
