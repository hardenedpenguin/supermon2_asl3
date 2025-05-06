<?php

include('session.inc');
include('amifunctions.inc');
include('authusers.php');
include('authini.php');

// Authentication check
if (
    !isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true ||
    !get_user_auth("ASTRELUSER")
) {
    die("<br><h3>ERROR: You must log in to use the 'Iax/Rpt/DP RELOAD' function!</h3>");
}

// Input processing
$Node = isset($_POST['node']) ? trim(strip_tags($_POST['node'])) : null;
$localnode = isset($_POST['localnode']) ? trim(strip_tags($_POST['localnode'])) : null;

if (empty($localnode)) {
    die("ERROR: Local node identifier is missing.");
}

// Load INI configuration
$supermonIniFile = get_ini_name($_SESSION['user']);

if (!file_exists($supermonIniFile)) {
    die("ERROR: Couldn't load configuration file: " . htmlspecialchars($supermonIniFile));
}

$config = parse_ini_file($supermonIniFile, true);
if ($config === false) {
    die("ERROR: Failed to parse configuration file: " . htmlspecialchars($supermonIniFile));
}

if (!isset($config[$localnode])) {
    die("ERROR: Node " . htmlspecialchars($localnode) . " is not defined in configuration file: " . htmlspecialchars($supermonIniFile));
}

// Connect to Asterisk Manager Interface (AMI)
$fp = AMIconnect($config[$localnode]['host']);
if ($fp === false) {
    die("ERROR: Could not connect to Asterisk Manager on host: " . htmlspecialchars($config[$localnode]['host']));
}

$loginSuccess = AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']);
if ($loginSuccess === false) {
    fclose($fp);
    die("ERROR: Could not login to Asterisk Manager.");
}

// Process reload request
$button = isset($_POST['button']) ? trim(strip_tags($_POST['button'])) : null;

if ($button === 'astreload') {
    AMIcommand($fp, "rpt reload");
    sleep(1);
    AMIcommand($fp, "iax2 reload");
    sleep(1);
    AMIcommand($fp, "extensions reload");

    echo "<b>Reloaded rpt.conf, iax.conf, extensions.conf at node - " . htmlspecialchars($localnode) . "</b>";
} else {
    echo "No reload action requested or invalid button specified.";
}

// Log off and clean up
AMIlogoff($fp);

?>
