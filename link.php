<?php
/**
 * link.php: Main setup and controller for the Supermon node display page.
 *
 * This script initializes the environment, loads configuration and data,
 * processes user input (node list, display preferences), and then includes
 * separate files to generate the JavaScript functionality and the HTML user interface.
 */

include("session.inc");
include("user_files/global.inc");
include("common.inc");
include_once("authusers.php");
include_once("authini.php");
include("header.inc");

// Get the list of nodes requested in the URL (e.g., link.php?nodes=1234,5678).
// Basic security measures (trim, strip_tags) are applied.
$parms = @trim(strip_tags($_GET['nodes']));
// Split the comma-separated node string into an array.
$passedNodes = explode(',', $parms);

// Set a far-future expiration time for cookies.
$expiretime = 2147483645; // Approx year 2038

// Ensure session variables for user login status exist, even if empty.
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = ""; // Current logged-in username
}
if (!isset($_SESSION['sm61loggedin'])) {
    $_SESSION['sm61loggedin'] = false; // Flag indicating if user is logged in
}

// Check if any nodes were actually provided in the URL.
// If not, stop the script with an error message.
if (count($passedNodes) == 0 || $parms === '') {
    // Use die() for critical errors like missing input.
    die ("Please provide a properly formated URI. (ie link.php?nodes=1234 | link.php?nodes=1234,2345)");
}

// --- Display Preference Handling (from Cookies) ---
$Displayed_Nodes = "999"; // Default: Max number of nodes to show per table
$Display_Count = 0;      // Default: Don't show node count summary
$Show_All = "1";         // Default: Show nodes even if never keyed
$Show_Detail = "1";      // Default: Show detailed view (with extra columns)

// Check if a cookie named 'display-data' exists.
if (isset($_COOKIE['display-data'])) {
    // Loop through the settings stored in the cookie array.
    foreach ($_COOKIE['display-data'] as $name => $value) {
        $name_safe = htmlspecialchars($name);
        $value_safe = htmlspecialchars($value);
        switch ($name_safe) {
            case "number-displayed":
                $Displayed_Nodes = ($value_safe === "0" || $value_safe === '') ? "999" : $value_safe;
                break;
            case "show-number":
                $Display_Count = intval($value_safe);
                break;
            case "show-all":
                $Show_All = intval($value_safe);
                break;
            case "show-detailed":
                $Show_Detail = intval($value_safe);
                break;
        }
    }
}

// Persist the 'show-detailed' setting back into the cookie for next visit.
setcookie("display-data[show-detailed]", $Show_Detail, $expiretime);

// Set CSS class names based on whether the detailed view is enabled ($Show_Detail == 1).
// These class names are likely used in CSS to adjust sizes of buttons, text, etc.
if ($Show_Detail == 1) {
    $SUBMITTER = "submit";
    $SUBMIT_SIZE = "submit";
    $TEXT_SIZE = "text-normal";
} else {
    $SUBMITTER = "submit-large";
    $SUBMIT_SIZE = "submit-large";
    $TEXT_SIZE = "text-large";
}

// Get the path to the AllStar node list text file (defined in common.inc).
$db = $ASTDB_TXT;
$astdb = array();

if (file_exists($db)) {
    // Open the file for reading.
    $fh = fopen($db, "r");
    if ($fh && flock($fh, LOCK_SH)) {
        while (($line = fgets($fh)) !== FALSE) {
            $arr = preg_split("/\|/", trim($line), 4); // Limit to 4 parts for robustness
            if(isset($arr[0]) && $arr[0] !== '') { // Ensure node number is valid
                 $astdb[$arr[0]] = $arr;
            }
        }
        flock($fh, LOCK_UN);
        fclose($fh);
    }
}

// Get the specific INI file name based on the logged-in user (from authini.php).
$SUPINI = get_ini_name($_SESSION['user']);

if (!file_exists($SUPINI)) {
    die("Couldn't load configuration file: " . htmlspecialchars($SUPINI));
}
// Parse the INI file into a structured array ($config). Sections are keys (e.g., [1234]).
$config = parse_ini_file($SUPINI, true);
// Check if parsing failed.
if ($config === false) {
     die("Error parsing configuration file: " . htmlspecialchars($SUPINI));
}

// Create a new array ($nodes) containing only the nodes requested in the URL
// that *also* exist as sections in the loaded INI file ($config).
$nodes = array();
foreach ($passedNodes as $node) {
    $trimmed_node = trim($node);
    if ($trimmed_node !== '' && isset($config[$trimmed_node])) {
        $nodes[] = $trimmed_node;
    }
}

// Check if *any* valid nodes remain after filtering against the INI file.
if (count($nodes) === 0) {
     die ("None of the specified nodes were found in the configuration file: " . htmlspecialchars($SUPINI));
}

// Rebuild the $parms string using only the validated nodes.
// This validated list is passed to the JavaScript for the SSE connection.
$parms = implode(',', $nodes);

// Make key variables available within the scope of the files included below.
// This is important because `include` brings code in, but doesn't automatically inherit all variables perfectly.
global $parms, $nodes, $config, $astdb, $Show_Detail, $SUBMITTER, $SUBMIT_SIZE, $TEXT_SIZE;
global $WELCOME_MSG, $WELCOME_MSG_LOGGED, $system_type, $EXTN, $IRLPLOG, $DATABASE_TXT;
global $HAMCLOCK_ENABLED, $HAMCLOCK_URL, $user, $Displayed_Nodes, $Display_Count, $Show_All;
// Get the current session ID *after* session_start() has been called within session.inc
$current_session_id = session_id();

// Load the JavaScript responsible for Server-Sent Events and dynamic table updates.
include("link_scripts.php");
// Load the PHP code responsible for generating the actual HTML structure (forms, tables, etc.).
include("link_ui.php");

?>
