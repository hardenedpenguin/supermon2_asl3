<?php

include('session.inc');
include('amifunctions.inc');
include('common.inc');
include('authini.php');

// Verify user session is active
if (!isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true) {
    die("<br><h3>ERROR: You Must login to use these functions!</h3>");
}

// Sanitize input from GET parameters: 'node' and 'cmd'
$node = @trim(strip_tags($_GET['node'] ?? ''));
$cmd  = @trim(strip_tags($_GET['cmd'] ?? ''));

// Validate required inputs
if (empty($node)) {
    die("ERROR: Node parameter is missing or empty.");
}
if (empty($cmd)) {
    die("ERROR: Command parameter is missing or empty.");
}

// Determine the appropriate INI config file for the logged-in user
$SUPINI = get_ini_name($_SESSION['user']);

// Ensure the config file exists and is readable
if (!file_exists($SUPINI)) {
    die("ERROR: Configuration file '$SUPINI' could not be found.");
}

// Parse the INI file with section support
$config = parse_ini_file($SUPINI, true);
if ($config === false) {
    die("ERROR: Failed to parse configuration file '$SUPINI'. Check its format.");
}

// Validate that the requested node exists in the config
if (!isset($config[$node])) {
    die("ERROR: Node '$node' is not defined in the configuration file '$SUPINI'.");
}

// Connect to the Asterisk Manager Interface (AMI) for the specified node
$fp = AMIconnect($config[$node]['host']);
if ($fp === FALSE) {
    die("ERROR: Could not connect to AMI host '{$config[$node]['host']}' for node '$node'.");
}

// Log in to AMI using the credentials from the config
if (AMIlogin($fp, $config[$node]['user'], $config[$node]['passwd']) === FALSE) {
    die("ERROR: Could not login to AMI host '{$config[$node]['host']}' for node '$node' with user '{$config[$node]['user']}'.");
}

// Replace %node% placeholder in the command string with actual node value
$cmdString = preg_replace("/%node%/", $node, $cmd);

// Generate a unique ActionID to track this specific command
$actionRand = mt_rand(10000, 99999);
$actionID   = 'smCmd_' . $actionRand;

// Build the AMI command string
$amiCommand = "ACTION: COMMAND\r\n";
$amiCommand .= "COMMAND: $cmdString\r\n";
$amiCommand .= "ActionID: $actionID\r\n";
$amiCommand .= "\r\n";

// Send the command to AMI and handle the response
if (@fwrite($fp, $amiCommand) > 0) {
    $rptStatus = AMIget_response($fp, $actionID);
    print "<pre>\n";
    print "===== Executing on Node '$node': $cmdString =====\n\n";
    print htmlspecialchars($rptStatus, ENT_QUOTES, 'UTF-8');
    print "\n===== End of Response =====\n</pre>\n";
} else {
    die("ERROR: Failed to send command '$cmdString' to node '$node'.");
}

// Gracefully log off from AMI
AMIlogoff($fp);

?>
