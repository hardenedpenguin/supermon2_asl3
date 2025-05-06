<?php

// Start a new session or resume an existing one
session_start(['name' => 'supermon61']);

// Check if login status is set
if (!array_key_exists('sm61loggedin', $_SESSION)) {
    $_SESSION['sm61loggedin'] = false;
}

// Define session lifetime in seconds (6 hours)
$sessionLifetime = 6 * 60 * 60; // 21600 seconds

// Set or check session creation time
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time(); // Set session creation time
} else {
    // Check if session lifetime has expired
    if (time() - $_SESSION['created'] > $sessionLifetime) {
        // Session has expired, destroy it
        session_unset();
        session_destroy();
        session_start(['name' => 'supermon61']); // Optionally start a fresh session
        $_SESSION['sm61loggedin'] = false;
        $_SESSION['session_expired'] = true; // Optional flag to show a message on frontend
    }
}
