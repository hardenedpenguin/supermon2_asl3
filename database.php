<?php

include("session.inc");
include("amifunctions.inc");
include("user_files/global.inc");
include("common.inc");
include("authusers.php");
include("authini.php");

if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("DBTUSER")))  {
    die ("<br><h3>ERROR: You Must login to use the 'Database' function!</h3>");
}

?>
<html>
<head>
<title>AllStar database.txt file contents</title>
</head>
<body style="background-color:powderblue;">

<?php
$Node = trim(strip_tags($_GET['node']));
$localnode = @trim(strip_tags($_GET['localnode']));

$SUPINI = get_ini_name($_SESSION['user']);

if (!file_exists("$SUPINI")) {
    die("Couldn't load $SUPINI file.\n");
}

$config = parse_ini_file("$SUPINI", true);

if (!isset($config[$localnode])) {
    die("Node $localnode is not in $SUPINI file.");
}

if (($fp = AMIconnect($config[$localnode]['host'])) === FALSE) {
    die("Could not connect to Asterisk Manager.");
}

if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
    die("Could not login to Asterisk Manager.");
}

/**
 * Sends a command to the Asterisk Manager Interface (AMI) to be executed.
 *
 * @param resource $fp The connection resource for the AMI.
 * @param string $localnode The node name associated with the command.
 * @param string $cmd The command to execute on the AMI.
 */
function sendCmdtAMI($fp, $localnode, $cmd) {
    AMIcommand($fp, "$cmd");
}

/**
 * Sends a command to the Asterisk Manager Interface (AMI) and returns the result.
 *
 * @param resource $fp The connection resource for the AMI.
 * @param string $localnode The node name associated with the command.
 * @param string $cmd The command to execute on the AMI.
 * @return string The result from executing the command.
 */
function getDataAMI($fp, $localnode, $cmd) {
    return AMIcommand($fp, $cmd);
}

$DATABASE_TXT = getDataAMI($fp, $localnode, "database show");

$file = $DATABASE_TXT;
$file = str_replace('--END COMMAND--', '', $file);
$file = nl2br("$file");
$today = exec("date");

print "<br><b><u>$today - Database from node - $localnode</u></b></br>";

if ($file == "") {
    print "<p>---NONE---</p>";
} else {
    print "<br>$file<br>";
}

// Log off from AMI after processing
AMIlogoff($fp);
?>

</body>
</html>
