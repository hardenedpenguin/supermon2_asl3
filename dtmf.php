<?php

include("session.inc");
include('amifunctions.inc');
include('user_files/global.inc');
include('authusers.php');
include('common.inc');
include('authini.php');

// Retrieve DTMF and localnode from POST data, ensuring any HTML tags are removed and input is trimmed
$dtmf = @trim(strip_tags($_POST['node']));
$localnode = @trim(strip_tags($_POST['localnode']));

// Check if the user is logged in and has the necessary DTMFUSER authorization
if (($_SESSION['sm61loggedin'] === true) && (get_user_auth("DTMFUSER"))) {

    // Validate that the DTMF value is provided
    if ($dtmf == '') {
        die("Please provide a DTMF command.\n");
    }

    // Get the INI file name associated with the logged-in user
    $SUPINI = get_ini_name($_SESSION['user']);

    // Check if the INI file exists, if not, terminate with an error message
    if (!file_exists("$SUPINI")) {
        die("Couldn't load $SUPINI file.\n");
    }

    // Parse the INI file into an associative array for configuration settings
    $config = parse_ini_file("$SUPINI", true);

    // Validate that the node exists in the configuration, if not, terminate with an error message
    if (!isset($config[$localnode])) {
        die("Node $localnode is not in $SUPINI file.");
    }

    // Establish a connection to the Asterisk Manager Interface (AMI) using the node's host
    if (($fp = AMIconnect($config[$localnode]['host'])) === FALSE) {
        die("Could not connect to Asterisk Manager.");
    }

    // Attempt to login to the AMI using the configured user credentials
    if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
        die("Could not login to Asterisk Manager.");
    }

    // Execute the DTMF command on the specified local node
    do_dtmf_cmd($fp, $localnode, $dtmf);

    // Log off from the AMI connection after the command is executed (good practice)
    AMIlogoff($fp);

} else {
    print "<br><h3>ERROR: You Must login to use the 'DTMF' function!</h3>";
}

/**
 * Sends a DTMF command via the Asterisk Manager Interface (AMI).
 *
 * @param resource $fp The AMI connection resource.
 * @param string $localnode The target node number.
 * @param string $dtmf The DTMF command string to send.
 */
function do_dtmf_cmd($fp, $localnode, $dtmf)
{
    // Send the DTMF command to the Asterisk Manager Interface using the rpt fun command
    $AMIResponse = AMIcommand($fp, "rpt fun $localnode $dtmf");

    // Output feedback to the user indicating the DTMF command being executed
    print "<b>Executing DTMF command '$dtmf' on node $localnode</b>";
}
?>
