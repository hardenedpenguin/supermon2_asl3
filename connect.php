<?php

include('session.inc');

// Check if user is logged in
if (!isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true) {
    header('Content-Type: text/plain');
    die("{\"status\": \"error\", \"message\": \"Please login to use connect/disconnect functions.\"}");
}

// Load required dependencies for user authentication, AMI access, and config
require_once('authusers.php');
require_once('user_files/global.inc');
require_once('amifunctions.inc');
require_once('common.inc');
require_once('authini.php');

header('Content-Type: text/html; charset=utf-8');

// Sanitize and extract user input
$remotenode = trim(strip_tags($_POST['remotenode'] ?? ''));
$perm       = trim(strip_tags($_POST['perm'] ?? ''));
$button     = trim(strip_tags($_POST['button'] ?? ''));
$localnode  = trim(strip_tags($_POST['localnode'] ?? ''));

// Validate local node number (must be 4 or more digits)
if (!preg_match("/^\d{4,}$/", $localnode)) {
    die("ERROR: Please provide a valid local node number.\n");
}

// Validate remote node number if action requires it
$actions_needing_remote = ['connect', 'monitor', 'localmonitor', 'disconnect'];
if (in_array($button, $actions_needing_remote)) {
    if (empty($remotenode) || !preg_match("/^\d{4,}$/", $remotenode)) {
        die("ERROR: Please provide a valid remote node number.\n");
    }
}

// Get the user-specific INI config file
$SUPINI = get_ini_name($_SESSION['user']);
if (!file_exists($SUPINI)) {
    die("ERROR: Configuration file not found.\n");
}

// Parse the INI config and ensure required keys exist
$config = @parse_ini_file($SUPINI, true);
if (
    !$config ||
    !isset($config[$localnode]['host'], $config[$localnode]['user'], $config[$localnode]['passwd'])
) {
    die("ERROR: Configuration missing or incomplete for local node.\n");
}

// Connect to the Asterisk AMI server
$fp = AMIconnect($config[$localnode]['host']);
if (!$fp) {
    die("ERROR: Could not connect to AMI.\n");
}

// Authenticate with AMI using credentials from config
if (!AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd'])) {
    AMIlogout($fp);
    die("ERROR: AMI login failed.\n");
}

// Initialize action-related variables
$ilink = null;
$actionDescription = '';
$permissionDenied = false;

// Map action button to AMI ilink command codes
switch ($button) {
    case 'connect':
        // Check if user has CONNECTUSER permission
        if (get_user_auth("CONNECTUSER")) {
            // Use permanent link code if applicable
            $ilink = ($perm === 'on' && get_user_auth("PERMUSER")) ? 13 : 3;
            $actionDescription = "Connect";
        } else {
            $permissionDenied = true;
        }
        break;

    case 'monitor':
        if (get_user_auth("MONUSER")) {
            $ilink = ($perm === 'on' && get_user_auth("PERMUSER")) ? 12 : 2;
            $actionDescription = "Monitor";
        } else {
            $permissionDenied = true;
        }
        break;

    case 'localmonitor':
        if (get_user_auth("LMONUSER")) {
            $ilink = ($perm === 'on' && get_user_auth("PERMUSER")) ? 18 : 8;
            $actionDescription = "Local Monitor";
        } else {
            $permissionDenied = true;
        }
        break;

    case 'disconnect':
        if (get_user_auth("DISCUSER")) {
            $ilink = ($perm === 'on' && get_user_auth("PERMUSER")) ? 11 : 1;
            $actionDescription = "Disconnect";
        } else {
            $permissionDenied = true;
        }
        break;

    default:
        AMIlogout($fp);
        die("ERROR: Invalid action specified.\n");
}

// Output result or permission denial
if ($permissionDenied) {
    print "<b>Permission Denied:</b> You do not have access to perform this action.<br>\n";
} elseif ($ilink !== null) {
    // Build and send the AMI command for ilink action
    $command = "rpt cmd $localnode ilink $ilink $remotenode";
    $AMIResponse = AMIcommand($fp, $command);

    if ($AMIResponse !== false) {
        print "<b>Success: $actionDescription completed.</b><br>\n";
    } else {
        print "<b>Failed: $actionDescription failed.</b><br>\n";
    }
} else {
    print "<b>Error:</b> Action could not be processed.<br>\n";
}

// Logout from AMI session
AMIlogoff($fp);
?>
