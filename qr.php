<?php
// QR Code Generator for Session URLs (Local Implementation)

require_once 'functions.php';

// Get session token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    die('Session token required');
}

// Validate session
$session = getSession($token);
if (!$session) {
    http_response_code(404);
    die('Session not found');
}

// Generate session URL
$sessionUrl = getSessionUrl($token);

// If this is an image request, generate QR code data
if (isset($_GET['img']) && $_GET['img'] === '1') {
    // Simple QR code implementation using QR Server API (more reliable)
    $size = intval($_GET['size'] ?? 200);
    $size = min(max($size, 100), 500); // Limit size between 100-500px
    
    // Use QR Server API which is more CORS-friendly
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($sessionUrl);
    
    // Fetch the QR code and serve it directly
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'Mozilla/5.0 (compatible; FileTransfer/1.0)'
        ]
    ]);
    
    $qrData = file_get_contents($qrUrl, false, $context);
    
    if ($qrData) {
        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        echo $qrData;
    } else {
        // Fallback: Generate a simple text-based QR placeholder
        header('Content-Type: image/svg+xml');
        $svg = generateFallbackQR($sessionUrl, $size);
        echo $svg;
    }
    exit;
}

// Generate fallback SVG QR code placeholder
function generateFallbackQR($url, $size) {
    $shortUrl = parse_url($url, PHP_URL_HOST) . '/...' . substr($url, -10);
    return '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
        <text x="50%" y="40%" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#666">QR Code</text>
        <text x="50%" y="55%" text-anchor="middle" font-family="Arial, sans-serif" font-size="10" fill="#999">Scan to access:</text>
        <text x="50%" y="70%" text-anchor="middle" font-family="Arial, sans-serif" font-size="8" fill="#999">' . htmlspecialchars($shortUrl) . '</text>
    </svg>';
}

// Otherwise, show QR code page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code - <?php echo htmlspecialchars($session['customer_name']); ?></title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .qr-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .qr-header {
            margin-bottom: 30px;
        }
        
        .qr-title {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .qr-subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .qr-code {
            margin: 30px 0;
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
        }
        
        .qr-code img {
            display: block;
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        
        .qr-loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #666;
            font-size: 14px;
        }
        
        .session-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .session-info h3 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-item {
            margin-bottom: 10px;
            font-size: 14px;
            color: #555;
        }
        
        .info-label {
            font-weight: 500;
            color: #2c3e50;
            margin-right: 8px;
        }
        
        .session-url {
            background: #e9ecef;
            padding: 10px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            margin-top: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .session-url:hover {
            background: #dee2e6;
        }
        
        .instructions {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        
        .instructions h4 {
            color: #1976d2;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .instructions ol {
            margin-left: 20px;
            color: #555;
        }
        
        .instructions li {
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .actions {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #219a52;
            transform: translateY(-2px);
        }
        
        .copy-feedback {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .copy-feedback.show {
            opacity: 1;
            transform: translateY(0);
        }
        
        @media (max-width: 480px) {
            .qr-container {
                padding: 20px;
            }
            
            .actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
            }
        }
        
        @media print {
            body {
                background: white;
            }
            
            .qr-container {
                box-shadow: none;
                border: 1px solid #ddd;
            }
            
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="copy-feedback" id="copyFeedback">
        ✅ URL copied to clipboard!
    </div>
    
    <div class="qr-container">
        <div class="qr-header">
            <h1 class="qr-title">📱 Session QR Code</h1>
            <p class="qr-subtitle">Scan to access file exchange</p>
        </div>
        
        <div class="qr-code">
            <img id="qrImage" 
                 src="qr.php?token=<?php echo urlencode($token); ?>&img=1&size=250" 
                 alt="QR Code for <?php echo htmlspecialchars($session['customer_name']); ?>" 
                 loading="lazy">
            <div class="qr-loading" id="qrLoading">Loading QR Code...</div>
        </div>
        
        <div class="session-info">
            <h3>📋 Session Details</h3>
            <div class="info-item">
                <span class="info-label">Customer:</span>
                <?php echo htmlspecialchars($session['customer_name']); ?>
            </div>
            <div class="info-item">
                <span class="info-label">Token:</span>
                <?php echo htmlspecialchars($session['token']); ?>
            </div>
            <div class="info-item">
                <span class="info-label">Created:</span>
                <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?>
            </div>
            <div class="info-item">
                <span class="info-label">Expires:</span>
                <?php echo date('M j, Y g:i A', strtotime($session['expires_at'])); ?>
            </div>
            <?php if ($session['notes']): ?>
                <div class="info-item">
                    <span class="info-label">Notes:</span>
                    <?php echo htmlspecialchars($session['notes']); ?>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="info-label">URL:</span>
                <div class="session-url" onclick="copyUrl()" title="Click to copy">
                    <?php echo htmlspecialchars($sessionUrl); ?>
                </div>
            </div>
        </div>
        
        <div class="instructions">
            <h4>📖 How to use this QR code:</h4>
            <ol>
                <li>Show this QR code to your customer</li>
                <li>Customer opens camera app on their phone</li>
                <li>Customer points camera at the QR code</li>
                <li>Customer taps the notification to open the link</li>
                <li>Customer can now upload and download files</li>
            </ol>
        </div>
        
        <div class="actions">
            <a href="admin.php?session=<?php echo urlencode($token); ?>" class="btn btn-secondary">
                ← Back to Session
            </a>
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print QR Code
            </button>
            <button onclick="copyUrl()" class="btn btn-success">
                📋 Copy URL
            </button>
            <a href="<?php echo htmlspecialchars($sessionUrl); ?>" target="_blank" class="btn btn-success">
                🔗 Test Link
            </a>
        </div>
    </div>
    
    <script>
        // Copy URL to clipboard function
        function copyUrl() {
            const url = '<?php echo addslashes($sessionUrl); ?>';
            navigator.clipboard.writeText(url).then(function() {
                showCopyFeedback();
            }).catch(function() {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = url;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                showCopyFeedback();
            });
        }
        
        function showCopyFeedback() {
            const feedback = document.getElementById('copyFeedback');
            feedback.classList.add('show');
            setTimeout(() => {
                feedback.classList.remove('show');
            }, 3000);
        }
        
        // QR code error handling
        document.addEventListener('DOMContentLoaded', function() {
            const qrImage = document.getElementById('qrImage');
            const qrLoading = document.getElementById('qrLoading');
            
            qrImage.addEventListener('load', function() {
                qrLoading.style.display = 'none';
            });
            
            qrImage.addEventListener('error', function() {
                console.log('QR code failed to load, trying fallback...');
                qrLoading.style.display = 'block';
                qrLoading.textContent = 'Generating QR Code...';
                
                // Try fallback after a delay
                setTimeout(() => {
                    this.src = 'qr.php?token=<?php echo urlencode($token); ?>&img=1&size=250&fallback=1&_=' + Date.now();
                }, 1000);
            });
        });
    </script>
</body>
</html>
