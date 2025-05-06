<?php
// Include necessary files
include("session.inc");
include("user_files/global.inc");
include("common.inc");
include("authusers.php");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>IRLP Messages Log</title>
    <style>
        /* White text on black background theme */
        body {
            background-color: black;
            color: white;
            font-family: sans-serif;
            margin: 15px;
        }

        h1 {
            color: #00FF00; /* Green header color */
            border-bottom: 1px solid #555;
            padding-bottom: 5px;
        }

        pre {
            font-family: monospace;
            font-size: 16px;
            background-color: black;
            color: white;
            border: 1px solid #444;
            padding: 15px;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin-top: 10px;
        }

        .error {
            color: #FF6347; /* Tomato red */
            font-weight: bold;
            background-color: #330000; /* Dark red */
            padding: 10px;
            border: 1px solid #FF6347;
            display: inline-block;
        }

        .log-header {
            font-weight: bold;
            margin-bottom: 5px;
            color: #ccc;
        }

        a {
            color: #66b3ff; /* Light blue for links */
        }

        a:visited {
            color: #cc99ff; /* Light purple for visited links */
        }

    </style>
</head>
<body>

<h1>IRLP Messages Log</h1>

<?php
// Check if the user is logged in and has permission to view the log
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && isset($IRLP_LOG) && get_user_auth("IRLPLOGUSER")) {
    $logFilePath = $IRLP_LOG; // Log file path from global settings

    // Display the log file path
    echo '<div class="log-header">Log File: ' . htmlspecialchars($logFilePath) . '</div>';
    echo '<pre>'; // Start preformatted block for log content

    // Check if the log file exists and is readable
    if (file_exists($logFilePath) && is_readable($logFilePath)) {
        // Read and display the log content with protection against XSS
        echo htmlspecialchars(file_get_contents($logFilePath));
    } else {
        // If the log file is not found or cannot be read
        echo "\n\nIRLP Log is not available or cannot be read.\nCheck path and permissions.\n";
    }

    echo '</pre>'; // End preformatted block

} else {
    // User is not logged in or lacks permissions
    echo '<p class="error">ERROR: You must login and have the required permissions to view this log!</p>';
}
?>

</body>
</html>
