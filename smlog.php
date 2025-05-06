<?php

include("session.inc");
include("user_files/global.inc");
include("common.inc");
include("authusers.php");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"> <!-- Added charset for good practice -->
    <title>Supermon2 Login/out Log</title>
    <style>
        body p {
            font-size: 14px;
        }
        .log-title {
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
            text-align: center; /* Use CSS for centering */
        }
        .error-message {
        }
    </style>
</head>
<body>

<?php
// Check if the user is logged in and has the specific authorization
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && get_user_auth("SMLOGUSER")) {

    // Get the log file name from configuration (defined in common.inc)
    $file = $SMLOGNAME;

    // Output the file name being displayed
    echo '<p>File: ' . htmlspecialchars($file) . '</p>'; // Use htmlspecialchars for security

    // Output the title
    echo '<p class="log-title">Supermon2 Login/Out LOG</p>'; // Use class for title

    // Check if the file exists and is readable
    if (file_exists($file) && is_readable($file)) {
        // Read the entire file into an array, each element is a line
        $content = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // More robust file reading

        // Reverse the array to show the latest entries first
        $content = array_reverse($content);

        // Loop through each line and display it
        foreach ($content as $line) {
            // Output each line wrapped in its own paragraph, escape HTML, preserve line breaks
            echo '<p>' . nl2br(htmlspecialchars($line)) . '</p>';
        }
    } else {
        // File not found or not readable error
        echo '<p><strong>Error:</strong> Log file not found or is not readable (' . htmlspecialchars($file) . ').</p>';
    }

} else {
    echo '<h3 class="error-message">ERROR: You must login and be authorized to use this function!</h3>';
}
?>

</body>
</html>