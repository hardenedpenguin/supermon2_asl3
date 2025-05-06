<?php

include("session.inc");
include("authusers.php");

// Check if the user is logged in and has the required 'RBTUSER' authorization.
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && get_user_auth("RBTUSER")) {

    // Output a message to the user indicating the reboot process has started.
    print "<b>Rebooting Server! Please wait...</b>";

    // Define the command to execute for rebooting the server.
    $rebootCommand = "sudo /usr/sbin/reboot";

    // Execute the reboot command.
    exec($rebootCommand);

    exit;
} else {
    print "<br><h3>ERROR: You must login and be authorized to use the 'Server REBOOT' function!</h3>";
}

?>