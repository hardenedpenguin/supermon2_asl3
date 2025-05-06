<?php
/**
 * Handles starting and stopping the AllStar service based on POST requests.
 * Requires user authentication via included session and auth files.
 */

include("session.inc");
include("authusers.php");

// Ensure the user is logged in
if (!isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true) {
    echo "<br><h3>ERROR: You Must login to use the 'AST START' or 'AST STOP' functions!</h3>";
    exit;
}

// Initialize variables
$button = '';
$outputLines = []; // To store output from exec()

// Safely get the button value from POST data
if (isset($_POST['button'])) {
    $button = trim(strip_tags($_POST['button']));
}

// Determine action based on button value
if ($button === 'astaron') {
    if (get_user_auth($ASTSTRUSER)) {
        echo "<strong>Starting up AllStar...</strong><br>\n";
        // Requires sudo configuration for www-data user.
        exec('sudo /usr/bin/astup.sh', $outputLines);
    } else {
        echo "<strong>Error:</strong> Not authorized to start AllStar.<br>\n";
    }

} elseif ($button === 'astaroff') {
    if (get_user_auth($ASTSTPUSER)) {
        echo "<strong>Shutting down AllStar...</strong><br>\n";
        // NOTE: Requires sudo configuration for www-data user.
        exec('sudo /usr/bin/astdn.sh', $outputLines);
    } else {
        echo "<strong>Error:</strong> Not authorized to stop AllStar.<br>\n";
    }
}

// Display output from the executed command, if any
if (!empty($outputLines)) {
    echo "<pre>\n";
    echo htmlspecialchars(implode("\n", $outputLines));
    echo "\n</pre>\n";
}

?>
