<?php
// Entry Point for File Transfer System

require_once 'functions.php';

// Check if this is an admin access or customer access
$token = $_GET['token'] ?? null;

if ($token) {
    // Customer access with session token
    $session = getSession($token);
    
    if (!$session) {
        // Invalid token
        showError('Invalid session token', 'The session token you provided is not valid or has been removed.');
    } elseif ($session['status'] === 'expired') {
        // Expired session
        showError('Session Expired', 'This session has expired. Please contact the sender for a new link.');
    } else {
        // Valid session - show customer interface (will be implemented later)
        echo "<h1>Customer Interface - Coming Soon</h1>";
        echo "<p>Session: " . htmlspecialchars($session['customer_name']) . "</p>";
        echo "<p>Token: " . htmlspecialchars($session['token']) . "</p>";
        echo "<p>Status: " . htmlspecialchars($session['status']) . "</p>";
        
        // Update activity
        updateSessionActivity($token);
    }
} else {
    // No token provided - redirect to admin
    header('Location: admin.php');
    exit;
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
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f5f5f5;
                margin: 0;
                padding: 40px 20px;
                text-align: center;
            }
            
            .error-container {
                max-width: 500px;
                margin: 0 auto;
                background: white;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            
            .error-icon {
                font-size: 48px;
                margin-bottom: 20px;
            }
            
            h1 {
                color: #e74c3c;
                margin-bottom: 20px;
                font-size: 24px;
            }
            
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 30px;
            }
            
            .btn {
                display: inline-block;
                background: #3498db;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
                transition: background 0.3s;
            }
            
            .btn:hover {
                background: #2980b9;
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
