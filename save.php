<?php

include("session.inc");
include("authusers.php");

// Ensure the user is logged in and has the required permission ('CFGEDUSER')
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("CFGEDUSER"))) {
    // If not authorized, display an error message and stop execution
    die("<br><h3>ERROR: You Must login to use the 'Save' function!</h3>");
}

// Get the edited content and filename from the POST request
$edit = $_POST["edit"];
$filename = $_POST["filename"];

// Sanitize the input: Remove carriage return characters (\r) which can cause issues on Linux/Unix systems
$edit = str_replace("\r", "", $edit);

// Print the header and a form to return to the main configuration editor page
print "<link type='text/css' rel='stylesheet' href='/supermon2/supermon.css'>";
print "<h1>Configuration File Saved</h1>"; // Added a title for context
print "<form name='REFRESH' method='POST' action='configeditor.php'>\n";
print "    <input name='return' tabindex='50' TYPE='SUBMIT' class='submit-large' Value='Return to Index'>\n";
print "</form>\n";
print "<hr>\n";

// Check if the target file is writable by the web server process
if (is_writable($filename)) {
    // Attempt to create a backup copy of the original file
    if (copy($filename, "$filename.bak")) {
        echo "<strong>Success, backup file created: <em>" . htmlspecialchars($filename) . ".bak</em></strong><br>\n";
    } else {
        echo "<strong style='color: orange;'>Warning: Could not create backup file: <em>" . htmlspecialchars($filename) . ".bak</em></strong><br>\n";
    }

    // Try to open the file for writing ('w' mode truncates the file)
    $handle = fopen($filename, 'w');
    if (!$handle) {
        // If opening fails, display an error and stop execution
        echo "<strong style='color: red;'>ERROR: Cannot open file for writing: <em>" . htmlspecialchars($filename) . "</em></strong>\n";
        exit;
    }

    // Try to write the edited content to the file
    if (fwrite($handle, $edit) === FALSE) {
        // If writing fails, display an error and stop execution
        echo "<strong style='color: red;'>ERROR: Cannot write to file: <em>" . htmlspecialchars($filename) . "</em></strong>\n";
        fclose($handle);
        exit;
    }

    // Close the file handle after successful writing
    fclose($handle);

    // Display a success message and show the content that was written
    // Convert newlines to <br> tags for HTML display and escape HTML characters for security
    echo "<strong>Success, wrote edits to file: <em>" . htmlspecialchars($filename) . "</em>:</strong><br><br>\n";
    echo "<pre style='border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9;'>" . htmlspecialchars($edit) . "</pre><br>\n"; // Use <pre> for better formatting display

} else {
    // If the file is not writable, display an error message
    echo "<strong style='color: red;'>ERROR: The file <em>" . htmlspecialchars($filename) . "</em> is not writable by the web server. Check permissions.</strong>\n";
}

?>