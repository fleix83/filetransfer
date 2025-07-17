<?php
// Admin Dashboard for File Transfer System - Redesigned UX

require_once 'functions.php';

// Handle session creation and file operations
$message = '';
$messageType = '';
$selectedSession = null;

// Check for last accessed session in cookie
if (isset($_COOKIE['last_session'])) {
    $cookieSession = $_COOKIE['last_session'];
    $testSession = getSession($cookieSession);
    if ($testSession && $testSession['status'] === 'active') {
        $selectedSession = $cookieSession;
    }
}

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
                $selectedSession = $token;
                
                // Set cookie to remember this session
                setcookie('last_session', $token, time() + (30 * 24 * 60 * 60), '/');
                
                $message = "Session created successfully! Share this URL:<br><a href='$sessionUrl' target='_blank'>$sessionUrl</a>";
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

// Handle session selection
if (isset($_GET['session'])) {
    $selectedSession = $_GET['session'];
    // Set cookie to remember this session
    setcookie('last_session', $selectedSession, time() + (30 * 24 * 60 * 60), '/');
}

// Get all sessions for sidebar
$sessions = getAllSessions();

// Get current session data if selected
$currentSession = null;
$currentFiles = [];
if ($selectedSession) {
    $currentSession = getSession($selectedSession);
    if ($currentSession && $currentSession['status'] === 'active') {
        $currentFiles = getSessionFiles($selectedSession);
    } else {
        $selectedSession = null; // Invalid session
    }
}
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
            font-family: ui-serif, Georgia, Cambria, "Times New Roman", Times, serif;
            background: #faf9f7;
            color: #2d3748;
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        /* Typography Hierarchy - Anthropic Style */
        h1, h2, h3, h4, h5, h6 {
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            font-weight: 500;
            letter-spacing: -0.01em;
        }
        
        /* Sidebar Styles - Anthropic Deep Blue */
        .sidebar {
            position: fixed;
            top: 0;
            left: -350px;
            width: 350px;
            height: 100vh;
            background: #1a2332;
            color: #e2e8f0;
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            border-right: 1px solid #2d3748;
        }
        
        .sidebar.open {
            left: 0;
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid #2d3748;
        }
        
        .sidebar-header h2 {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 16px;
            color: #f7fafc;
            letter-spacing: -0.01em;
        }
        
        .new-session-btn {
            width: 100%;
            background: #3182ce;
            color: white;
            border: none;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
        }
        
        .new-session-btn:hover {
            background: #2c5aa0;
            transform: translateY(-1px);
        }
        
        .sessions-list {
            padding: 0;
        }
        
        .session-item {
            padding: 16px 24px;
            border-bottom: 1px solid #2d3748;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .session-item:hover {
            background: #2d3748;
        }
        
        .session-item.active {
            background: #3182ce;
            border-left: 3px solid #4299e1;
        }
        
        .session-name {
            font-weight: 500;
            margin-bottom: 6px;
            color: #f7fafc;
            font-size: 14px;
        }
        
        .session-meta {
            font-size: 12px;
            color: #a0aec0;
            line-height: 1.4;
        }
        
        .session-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 6px;
            letter-spacing: 0.05em;
        }
        
        .status-active {
            background: rgba(56, 178, 172, 0.2);
            color: #38b2ac;
        }
        
        .status-expired {
            background: rgba(245, 101, 101, 0.2);
            color: #f56565;
        }
        
        /* Sidebar Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Main Content - Anthropic Style */
        .main-content {
            transition: margin-left 0.3s ease;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .menu-toggle {
            background: #3182ce;
            color: white;
            border: none;
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }
        
        .menu-toggle:hover {
            background: #2c5aa0;
            transform: translateY(-1px);
        }
        
        .page-title {
            font-size: 18px;
            font-weight: 500;
            color: #2d3748;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
        }
        
        .content-area {
            padding: 32px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        /* Welcome State - Anthropic Clean Design */
        .welcome-state {
            text-align: center;
            padding: 80px 32px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }
        
        .welcome-icon {
            font-size: 56px;
            margin-bottom: 24px;
            opacity: 0.7;
        }
        
        .welcome-title {
            font-size: 32px;
            color: #2d3748;
            margin-bottom: 16px;
            font-weight: 500;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            letter-spacing: -0.02em;
        }
        
        .welcome-text {
            color: #718096;
            margin-bottom: 32px;
            font-size: 16px;
            max-width: 480px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .create-session-btn {
            background: #3182ce;
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
        }
        
        .create-session-btn:hover {
            background: #2c5aa0;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
        }
        
        /* Session Interface - Anthropic Styling */
        .session-interface {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.1);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        
        .session-header {
            background: linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%);
            color: white;
            padding: 32px;
        }
        
        .session-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 8px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            letter-spacing: -0.01em;
        }
        
        .session-info {
            opacity: 0.9;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .session-body {
            padding: 32px;
        }
        
        /* Message Styles - Anthropic Muted Palette */
        .message {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            border: 1px solid;
        }
        
        .message.success {
            background: #f0fff4;
            color: #2f855a;
            border-color: #9ae6b4;
        }
        
        .message.error {
            background: #fed7d7;
            color: #c53030;
            border-color: #feb2b2;
        }
        
        /* File Upload Area - Clean Anthropic Style */
        .file-upload-area {
            border: 2px dashed #cbd5e0;
            border-radius: 12px;
            padding: 48px 32px;
            text-align: center;
            background: #f7fafc;
            margin-bottom: 32px;
            transition: all 0.2s;
        }
        
        .file-upload-area:hover,
        .file-upload-area.dragover {
            border-color: #3182ce;
            background: #ebf8ff;
            transform: translateY(-1px);
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
            font-size: 14px;
            margin-bottom: 24px;
        }
        
        .choose-files-btn {
            background: #3182ce;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            font-weight: 500;
        }
        
        .choose-files-btn:hover {
            background: #2c5aa0;
            transform: translateY(-1px);
        }
        
        /* File List - Anthropic Clean Design */
        .files-section h3 {
            color: #2d3748;
            margin-bottom: 20px;
            font-size: 18px;
            font-family: -apple-system, BlinkMacSystemFont, 'SF Pro Display', 'Segoe UI', system-ui, sans-serif;
            font-weight: 500;
        }
        
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #fafafa;
            transition: all 0.2s;
        }
        
        .file-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
            border-color: #cbd5e0;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 500;
            color: #2d3748;
            margin-bottom: 4px;
            font-size: 14px;
        }
        
        .file-meta {
            font-size: 12px;
            color: #718096;
        }
        
        .file-actions {
            display: flex;
            gap: 8px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
        }
        
        .btn-download {
            background: #38b2ac;
            color: white;
        }
        
        .btn-download:hover {
            background: #319795;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: #e53e3e;
            color: white;
        }
        
        .btn-delete:hover {
            background: #c53030;
            transform: translateY(-1px);
        }
        
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
            background: linear-gradient(90deg, #3498db, #2980b9);
            width: 0%;
            transition: width 0.3s;
        }
        
        .empty-files {
            text-align: center;
            padding: 48px 24px;
            color: #718096;
        }
        
        .empty-icon {
            font-size: 40px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* QR Code and Copy buttons - Anthropic Style */
        .qr-btn, .copy-btn {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            cursor: pointer;
            backdrop-filter: blur(10px);
        }
        
        .qr-btn:hover, .copy-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: rgba(255, 255, 255, 0.4);
            transform: translateY(-1px);
        }
        
        .copy-btn {
            background: rgba(56, 178, 172, 0.8);
            border-color: rgba(56, 178, 172, 0.5);
        }
        
        .copy-btn:hover {
            background: rgba(56, 178, 172, 0.9);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 280px;
                left: -280px;
            }
            
            .content-area {
                padding: 20px;
            }
            
            .session-body {
                padding: 20px;
            }
            
            .file-upload-area {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>📁 Sessions</h2>
            <button class="new-session-btn" onclick="showNewSessionForm()">+ New Session</button>
        </div>
        
        <div class="sessions-list">
            <?php if (empty($sessions)): ?>
                <div style="padding: 20px; text-align: center; color: #bdc3c7;">
                    <p>No sessions yet.<br>Create your first session!</p>
                </div>
            <?php else: ?>
                <?php foreach ($sessions as $session): ?>
                    <div class="session-item <?php echo ($selectedSession === $session['token']) ? 'active' : ''; ?>" 
                         onclick="selectSession('<?php echo $session['token']; ?>')">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                            <div style="flex: 1;">
                                <div class="session-name"><?php echo htmlspecialchars($session['customer_name']); ?></div>
                                <div class="session-meta">
                                    <?php echo substr($session['token'], 0, 16); ?>...<br>
                                    <?php echo date('M j, Y', strtotime($session['created_at'])); ?><br>
                                    <?php echo count(getSessionFiles($session['token'])); ?> files
                                </div>
                                <div class="session-status status-<?php echo $session['status']; ?>">
                                    <?php echo $session['status']; ?>
                                </div>
                            </div>
                            <div style="margin-left: 10px;">
                                <a href="qr.php?token=<?php echo urlencode($session['token']); ?>" 
                                   target="_blank" 
                                   onclick="event.stopPropagation()"
                                   style="color: #bdc3c7; font-size: 16px; text-decoration: none; padding: 4px;"
                                   title="Show QR Code">
                                    📱
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="top-bar">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <div class="page-title">
                <?php if ($currentSession): ?>
                    File Exchange - <?php echo htmlspecialchars($currentSession['customer_name']); ?>
                <?php else: ?>
                    File Transfer Dashboard
                <?php endif; ?>
            </div>
        </div>
        
        <div class="content-area">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($currentSession): ?>
                <!-- Active Session Interface -->
                <div class="session-interface">
                    <div class="session-header">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                            <div style="flex: 1; min-width: 200px;">
                                <div class="session-title"><?php echo htmlspecialchars($currentSession['customer_name']); ?></div>
                                <div class="session-info">
                                    Session: <?php echo $currentSession['token']; ?><br>
                                    <?php if ($currentSession['notes']): ?>
                                        <?php echo htmlspecialchars($currentSession['notes']); ?><br>
                                    <?php endif; ?>
                                    Share URL: <a href="<?php echo getSessionUrl($currentSession['token']); ?>" target="_blank" style="color: white; text-decoration: underline;">
                                        <?php echo getSessionUrl($currentSession['token']); ?>
                                    </a><br>
                                    Expires: <?php echo date('M j, Y', strtotime($currentSession['expires_at'])); ?>
                                </div>
                            </div>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="qr.php?token=<?php echo urlencode($currentSession['token']); ?>" 
                                   target="_blank" 
                                   class="qr-btn">
                                    📱 QR Code
                                </a>
                                <button onclick="copySessionUrl()" class="copy-btn">
                                    📋 Copy URL
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="session-body">
                        <!-- File Upload Area -->
                        <div class="file-upload-area" id="uploadArea">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">Drag and drop files here</div>
                            <div class="upload-hint">or click to browse files</div>
                            <input type="file" id="fileInput" multiple style="display: none;">
                            <button type="button" class="choose-files-btn" onclick="document.getElementById('fileInput').click();">
                                Choose Files
                            </button>
                            <div style="margin-top: 10px; font-size: 12px; color: #999;">
                                Maximum file size: <?php echo formatFileSize(MAX_FILE_SIZE); ?>
                            </div>
                        </div>
                        
                        <div class="upload-progress" id="uploadProgress">
                            <div class="upload-progress-bar" id="uploadProgressBar"></div>
                        </div>
                        
                        <!-- Files List -->
                        <div class="files-section">
                            <h3>📋 Files (<?php echo count($currentFiles); ?>)</h3>
                            
                            <div id="filesList">
                                <?php if (empty($currentFiles)): ?>
                                    <div class="empty-files">
                                        <div class="empty-icon">📄</div>
                                        <p>No files uploaded yet.<br>Upload your first file above.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($currentFiles as $fileName => $fileInfo): ?>
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
                                                   class="btn-small btn-download">Download</a>
                                                <button type="button" class="btn-small btn-delete" 
                                                        onclick="deleteFile('<?php echo addslashes($selectedSession); ?>', '<?php echo addslashes($fileName); ?>')">
                                                    Delete
                                                </button>
                                                <input type="hidden" value="<?php echo htmlspecialchars($fileName); ?>">
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Welcome State -->
                <div class="welcome-state">
                    <div class="welcome-icon">🚀</div>
                    <h1 class="welcome-title">Welcome to File Transfer</h1>
                    <p class="welcome-text">Create a new session to start sharing files securely with your customers.</p>
                    <button class="create-session-btn" onclick="showNewSessionForm()">Create New Session</button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- New Session Modal -->
    <div id="newSessionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000;">
        <div style="display: flex; align-items: center; justify-content: center; height: 100%;">
            <div style="background: white; padding: 30px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90%; overflow-y: auto;">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Create New Session</h2>
                
                <form method="POST">
                    <input type="hidden" name="action" value="create_session">
                    
                    <div style="margin-bottom: 20px;">
                        <label for="customer_name" style="display: block; margin-bottom: 5px; font-weight: 500;">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" 
                               style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px;"
                               placeholder="Enter customer or company name" required>
                    </div>
                    
                    <div style="margin-bottom: 30px;">
                        <label for="notes" style="display: block; margin-bottom: 5px; font-weight: 500;">Notes (Optional)</label>
                        <textarea id="notes" name="notes" 
                                  style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 6px; font-size: 16px; resize: vertical; min-height: 80px;"
                                  placeholder="Project details, purpose, or any additional notes..."></textarea>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="button" onclick="hideNewSessionForm()" 
                                style="background: #95a5a6; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            Cancel
                        </button>
                        <button type="submit" 
                                style="background: #3498db; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer;">
                            Create Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        }
        
        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        }
        
        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
        
        // Session selection
        function selectSession(token) {
            window.location.href = 'admin.php?session=' + encodeURIComponent(token);
        }
        
        // New session modal
        function showNewSessionForm() {
            document.getElementById('newSessionModal').style.display = 'block';
            closeSidebar();
        }
        
        function hideNewSessionForm() {
            document.getElementById('newSessionModal').style.display = 'none';
        }
        
        // Delete file function (AJAX to avoid page reload)
        function deleteFile(sessionToken, fileName) {
            if (!confirm('Are you sure you want to delete this file?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_file');
            formData.append('session_token', sessionToken);
            formData.append('file_name', fileName);
            
            const xhr = new XMLHttpRequest();
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    // Remove the file item from the list
                    const fileItems = document.querySelectorAll('.file-item');
                    fileItems.forEach(item => {
                        if (item.querySelector('input[value="' + fileName + '"]')) {
                            item.remove();
                        }
                    });
                    
                    // Update file count in header
                    const filesSection = document.querySelector('.files-section h3');
                    if (filesSection) {
                        const currentCount = parseInt(filesSection.textContent.match(/\d+/)[0]);
                        filesSection.textContent = `📋 Files (${currentCount - 1})`;
                    }
                    
                    // Show empty state if no files left
                    const remainingFiles = document.querySelectorAll('.file-item').length;
                    if (remainingFiles === 0) {
                        document.getElementById('filesList').innerHTML = `
                            <div class="empty-files">
                                <div class="empty-icon">📄</div>
                                <p>No files uploaded yet.<br>Upload your first file above.</p>
                            </div>
                        `;
                    }
                } else {
                    alert('Error deleting file');
                }
            });
            
            xhr.addEventListener('error', function() {
                alert('Error deleting file');
            });
            
            xhr.open('POST', 'admin.php');
            xhr.send(formData);
        }
        
        // Copy session URL to clipboard
        function copySessionUrl() {
            const url = '<?php echo addslashes(getSessionUrl($currentSession['token'] ?? '')); ?>';
            navigator.clipboard.writeText(url).then(function() {
                // Show success feedback
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.style.background = 'rgba(39, 174, 96, 1)';
                
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = 'rgba(39, 174, 96, 0.8)';
                }, 2000);
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                
                // Show success feedback
                const btn = document.querySelector('.copy-btn');
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
        
        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                hideNewSessionForm();
                closeSidebar();
            }
        });
        
        // File upload functionality (only if session is active)
        <?php if ($currentSession): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('fileInput');
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadProgressBar = document.getElementById('uploadProgressBar');
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
                // Validate session token before upload
                const sessionToken = '<?php echo addslashes($currentSession['token'] ?? ''); ?>';
                console.log('Starting upload for file:', file.name, 'Session token:', sessionToken);
                
                if (!sessionToken) {
                    console.error('Upload failed: No session token available');
                    alert('Upload failed: No valid session selected');
                    return;
                }
                
                const formData = new FormData();
                formData.append('file', file);
                formData.append('session_token', sessionToken);
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
                                console.error('Upload failed:', response.error);
                                alert('Upload failed: ' + response.error);
                            }
                        } catch (e) {
                            console.error('Upload response parsing error:', e, 'Response:', xhr.responseText);
                            alert('Upload failed: Invalid response from server');
                        }
                    } else {
                        console.error('Upload HTTP error:', xhr.status, xhr.statusText, 'Response:', xhr.responseText);
                        alert('Upload failed: Server error (HTTP ' + xhr.status + ')');
                    }
                });
                
                // Handle error
                xhr.addEventListener('error', function() {
                    uploadProgress.style.display = 'none';
                    console.error('Upload network error for file:', file.name, 'Session token:', sessionToken);
                    alert('Upload failed: Network error');
                });
                
                // Send request
                xhr.open('POST', 'upload.php');
                xhr.send(formData);
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>
