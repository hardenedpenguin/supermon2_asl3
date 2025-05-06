<?php

include("session.inc");
include('amifunctions.inc');
include('user_files/global.inc');
include('common.inc');
include('authini.php');

// Ensure the user is authenticated
if (!isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true) {
    die ("<br><h3>ERROR: You must login to use this function!</h3>");
}

// Validate and sanitize input parameters
if (!isset($_GET['node']) || !isset($_GET['cmd'])) {
    die("<br><h3>ERROR: Missing 'node' or 'cmd' parameter in the request.</h3>");
}

$node = trim(strip_tags($_GET['node']));
$cmd = trim(strip_tags($_GET['cmd']));

if (empty($node) || empty($cmd)) {
    die("<br><h3>ERROR: 'node' or 'cmd' parameter cannot be empty after sanitization.</h3>");
}

// Load per-user configuration file
$supIniPath = get_ini_name($_SESSION['user']);
if (!file_exists($supIniPath)) {
    die("ERROR: Configuration file '$supIniPath' could not be found or loaded.");
}

$config = parse_ini_file($supIniPath, true);
if ($config === false) {
    die("ERROR: Failed to parse configuration file '$supIniPath'. Check its format.");
}

// Ensure the node exists in the configuration
if (!isset($config[$node])) {
    die("ERROR: Node '$node' is not defined in the configuration file '$supIniPath'.");
}

// Attempt AMI socket connection
$fp = AMIconnect($config[$node]['host']);
if ($fp === FALSE) {
    die("ERROR: Could not connect to AMI host '{$config[$node]['host']}' for node '$node'.");
}

// Authenticate to AMI using credentials from the config
$loginSuccess = AMIlogin($fp, $config[$node]['user'], $config[$node]['passwd']);
if ($loginSuccess === FALSE) {
    fclose($fp); // Fallback close if login fails early
    die("ERROR: Could not login to AMI host '{$config[$node]['host']}' for node '$node' with user '{$config[$node]['user']}'. Check credentials.");
}

// Replace placeholder with the actual node in the command
$cmdString = preg_replace("/%node%/", $node, $cmd);

// Generate a unique ActionID for correlating AMI responses
$actionID = 'cpAction_' . mt_rand();

// Format the AMI command string
$amiCommand = "ACTION: COMMAND\r\n";
$amiCommand .= "COMMAND: $cmdString\r\n";
$amiCommand .= "ActionID: $actionID\r\n";
$amiCommand .= "\r\n";

// Send command to AMI and fetch response
if (fwrite($fp, $amiCommand) > 0) {
    $rptStatus = AMIget_response($fp, $actionID);

    // Output command and its result safely
    print "<pre>\n";
    print "===== " . htmlspecialchars($cmdString, ENT_QUOTES, 'UTF-8') . " =====\n";
    print htmlspecialchars($rptStatus, ENT_QUOTES, 'UTF-8');
    print "\n===== END =====\n";
    print "</pre>\n";

    // Log off AMI and close the connection
    AMILogoff($fp);
} else {
    fclose($fp); // Only close here if command failed to send
    die("ERROR: Failed to send command to node '$node' (Host: {$config[$node]['host']}).");
}
?>
