<?php

include("session.inc");
include('amifunctions.inc');
include("authusers.php");
include("authini.php");

// Check if the user is logged in and has the required 'FSTRESUSER' authorization
if (($_SESSION['sm61loggedin'] === true) && (get_user_auth("FSTRESUSER"))) {
    
    // Get and sanitize the 'localnode' parameter from the POST request
    $localnode = @trim(strip_tags($_POST['localnode']));
    
    // Get the user-specific INI file name based on the session username
    $SUPINI = get_ini_name($_SESSION['user']);

    // Check if the user-specific INI file exists
    if (!file_exists($SUPINI)) {
        die("Could not load configuration file: " . htmlspecialchars($SUPINI));
    }

    // Parse the INI file into an array
    $config = parse_ini_file($SUPINI, true);
    if ($config === false) {
        die("Error parsing configuration file: " . htmlspecialchars($SUPINI));
    }

    // Check if the specified node exists in the INI file's sections
    if (!isset($config[$localnode])) {
        die("Node '" . htmlspecialchars($localnode) . "' is not defined in configuration file: " . htmlspecialchars($SUPINI));
    }

    // Connect to the Asterisk Manager Interface (AMI) using the host from the INI file
    $fp = AMIconnect($config[$localnode]['host']);
    if ($fp === FALSE) {
        die("Could not connect to Asterisk Manager on host: " . htmlspecialchars($config[$localnode]['host']));
    }

    // Attempt to log in to the Asterisk Manager Interface with the provided credentials
    $loginSuccess = AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']);
    if ($loginSuccess === FALSE) {
        die("Could not login to Asterisk Manager for node '" . htmlspecialchars($localnode) . "' with user '" . htmlspecialchars($config[$localnode]['user']) . "'.");
    }

    // Execute the 'restart now' command on the Asterisk Manager
    $AMI1 = AMIcommand($fp, "restart now");

    // Log off from the Asterisk Manager Interface after the command is executed
    AMIlogoff($fp);

    // Output a success message indicating that the restart command was issued
    print "<b>Fast Restarting Asterisk Now on node: " . htmlspecialchars($localnode) . "</b>";
} else {
    // If the user is not logged in or does not have proper authorization, display an error
    print "<br><h3>ERROR: You must login and have appropriate permissions to use the 'RESTART' function!</h3>";
}
?>
