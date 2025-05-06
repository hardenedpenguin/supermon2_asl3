<?php
// Strict error reporting for development (optional, remove for production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

include("session.inc");
include("common.inc");
include("authusers.php");

// --- Configuration Check ---
// Check if $EXTNODES is defined *after* including common.inc
$extnodes_defined = isset($EXTNODES);
$extnodes_file = $extnodes_defined ? $EXTNODES : null; // Store the value if defined

// --- Authorization Check ---
$is_logged_in = isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true;
$is_authorized = $is_logged_in && function_exists('get_user_auth') && get_user_auth("EXNUSER");

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AllStar rpt_extnodes contents</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.4;
            background-color: #222; /* Dark background for the page */
            color: #eee;           /* Light default text color */
            margin: 20px;           /* Add some margin around the content */
        }

        /* Style the main heading */
        h1 {
            color: limegreen;       /* Green text color for H1 */
            text-align: center;     /* Center the heading */
            margin-bottom: 25px;    /* Add some space below the heading */
        }

        /* Style the container for the file content */
        .file-content-box {
            background-color: black;  /* Black background */
            color: white;           /* White text */
            border: 1px solid #ccc; /* Visible border (light gray) */
            padding: 15px;          /* Padding inside the box */
            white-space: pre-wrap;  /* Preserve whitespace, wrap lines */
            word-wrap: break-word;  /* Break long words if necessary */
            margin-top: 10px;       /* Space above the box */
            min-height: 100px;      /* Ensure box has some height even if empty */
            overflow-x: auto;       /* Add scrollbar if content overflows horizontally */
            font-family: monospace; /* Often better for config files */
        }

        /* Style the file path info */
        .file-info {
             font-weight: bold;
             margin-bottom: 5px;
             color: orange;        /* Orange text color for file path */
        }

        /* Style for error messages */
        h3.error-message {
            color: #FF6347; /* Tomato red - visible on dark background */
            background-color: #400; /* Dark red background for emphasis */
            border: 1px solid #FF6347;
            padding: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h1>rpt_extnodes File Content</h1>

<?php
// Check authorization first
if ($is_authorized) {
    // Then check if the config variable is set
    if (!$extnodes_defined) {
        echo '<h3 class="error-message">Configuration Error: $EXTNODES variable is not defined in common.inc.</h3>';
    } else {
        // Display file information (now styled orange)
        echo '<div class="file-info">File Path: ' . htmlspecialchars($extnodes_file) . '</div>';
        echo '<hr style="border-color: #555;">'; // Separator (darker gray for better contrast)

        // Display file content within the styled box
        echo '<pre class="file-content-box">'; // Use <pre> with the class

        // Check if the file exists and is readable
        if (file_exists($extnodes_file) && is_readable($extnodes_file)) {
            // Read and display file content safely
            echo htmlspecialchars(file_get_contents($extnodes_file));
        } elseif (!file_exists($extnodes_file)) {
            // File not found message
            echo "\n*** ERROR ***\n\n";
            echo "The AllStar rpt_extnodes file ('" . htmlspecialchars($extnodes_file) . "') does not exist.\n";
            echo "Please check the path and file permissions.";
        } else {
             // File exists but is not readable
            echo "\n*** ERROR ***\n\n";
            echo "The AllStar rpt_extnodes file ('" . htmlspecialchars($extnodes_file) . "') exists but is not readable.\n";
            echo "Please check file permissions.";
        }
        echo '</pre>'; // Close the styled preformatted block
    }
} else {
    // Display authorization error
    // Check if the reason is not logged in vs not authorized
    if (!$is_logged_in) {
         echo '<h3 class="error-message">Access Denied: You must be logged in to view this page.</h3>';
    } else {
         echo '<h3 class="error-message">Access Denied: You do not have the required permissions (EXNUSER) to view this page.</h3>';
    }
}
?>

</body>
</html>
