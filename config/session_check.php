<?php
define('SESSION_LIFESPAN', 3600); // 1 hour

// Set PHP session parameters before starting
ini_set('session.gc_maxlifetime', SESSION_LIFESPAN);  // server-side cleanup
ini_set('session.cookie_lifetime', SESSION_LIFESPAN); // browser cookie expiry

session_start();

// Check if logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index");
    exit();
}

// First request after login: store login time
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Enforce fixed 1 hour session lifespan
if (time() - $_SESSION['login_time'] >= SESSION_LIFESPAN) {
    session_unset();
    session_destroy();
    header("Location: index?timeout=1");
    exit();
}



