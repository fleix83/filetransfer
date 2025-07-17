<?php
// Entry Point for File Transfer System with Device Detection

require_once 'functions.php';

// Check if this is an admin access or customer access
$token = $_GET['token'] ?? null;

if ($token) {
    // Customer access with session token
    $session = getSession($token);
    
    if (!$session) {
        // Invalid token
        showError('Invalid Session Token', 'The session token you provided is not valid or has been removed.');
    } elseif ($session['status'] === 'expired') {
        // Expired session
        showError('Session Expired', 'This session has expired. Please contact the sender for a new link.');
    } else {
        // Valid session - detect device type and show appropriate interface
        $userAgent = $_SERVER['HTTP_USER_Agent'] ?? '';
        $isDesktop = detectDesktopDevice($userAgent);
        
        if ($isDesktop) {
            // Desktop/Admin interface - redirect to admin with session selected
            header('Location: admin.php?session=' . urlencode($token));
            exit;
        } else {
            // Mobile/Customer interface
            include 'customer.php';
            exit;
        }
    }
} else {
    // No token provided - redirect to admin
    header('Location: admin.php');
    exit;
}

/**
 * Detect if user is on desktop device (likely admin)
 * @param string $userAgent
 * @return bool
 */
function detectDesktopDevice($userAgent) {
    // Check for mobile indicators
    $mobileKeywords = [
        'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 
        'BlackBerry', 'Windows Phone', 'Opera Mini'
    ];
    
    foreach ($mobileKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return false; // Mobile device detected
        }
    }
    
    // Check for desktop browsers
    $desktopKeywords = [
        'Windows NT', 'Macintosh', 'X11', 'Linux'
    ];
    
    foreach ($desktopKeywords as $keyword) {
        if (stripos($userAgent, $keyword) !== false) {
            return true; // Desktop device detected
        }
    }
    
    // Default to mobile for unknown devices (safer for customer experience)
    return false;
}

/**
 * Show error page
 */
function showError($title, $message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?> - File Transfer</title>
        <style>
            body {
                font-family: DIN-2014, din-2014, "DIN Next", "Segoe UI", system-ui, sans-serif;
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
                margin-bottom: 30px;
            }
            
            .btn {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 30px;
                text-decoration: none;
                border-radius: 50px;
                font-weight: 500;
                transition: transform 0.3s;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">⚠️</div>
            <h1><?php echo htmlspecialchars($title); ?></h1>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="admin.php" class="btn">Go to Admin Dashboard</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
