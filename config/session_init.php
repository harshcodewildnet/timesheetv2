<?php
// Session lifetime in seconds
define('SESSION_TIMEOUT', 900);

// Must be before session_start()
ini_set('session.gc_maxlifetime', SESSION_TIMEOUT);
ini_set('session.cookie_lifetime', SESSION_TIMEOUT);

// Start the session
session_start();

// Check for inactivity
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    // Too much inactivity → destroy session
    session_unset();
    session_destroy();

    // Redirect to login page with optional timeout message
    // header("Location: index?timeout=1");
    echo "
    <script>
        alert('Session Timed Out!');
        window.location.href = 'index';
    </script>
    ";
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();
