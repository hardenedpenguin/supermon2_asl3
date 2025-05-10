<?php

include("session.inc");
include("user_files/global.inc");
include("common.inc");
include_once("authusers.php");
include_once("authini.php");
include("header.inc");

define('COOKIE_EXPIRE_TIME', 2147483645);

if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "";
}
if (!isset($_SESSION['sm61loggedin'])) {
    $_SESSION['sm61loggedin'] = false;
}

$nodes_param_raw = $_GET['nodes'] ?? '';
$nodes_param_sanitized = trim(strip_tags($nodes_param_raw));
$passedNodes = ($nodes_param_sanitized === '') ? [] : explode(',', $nodes_param_sanitized);

if (empty($passedNodes)) {
    die ("Please provide a properly formatted URI. (e.g., link.php?nodes=1234 or link.php?nodes=1234,2345)");
}

$default_displayed_nodes = "999";
$default_display_count = 0;
$default_show_all = "1";
$default_show_detail = "1";

$cookie_display_data = $_COOKIE['display-data'] ?? [];

$raw_displayed_nodes = htmlspecialchars($cookie_display_data['number-displayed'] ?? $default_displayed_nodes);
$Displayed_Nodes = ($raw_displayed_nodes === "0" || $raw_displayed_nodes === '') ? "999" : $raw_displayed_nodes;

$Display_Count = intval($cookie_display_data['show-number'] ?? $default_display_count);
$Show_All      = intval($cookie_display_data['show-all'] ?? $default_show_all);
$Show_Detail   = intval($cookie_display_data['show-detailed'] ?? $default_show_detail);

setcookie("display-data[show-detailed]", $Show_Detail, COOKIE_EXPIRE_TIME);

if ($Show_Detail == 1) {
    $SUBMITTER   = "submit";
    $SUBMIT_SIZE = "submit";
    $TEXT_SIZE   = "text-normal";
} else {
    $SUBMITTER   = "submit-large";
    $SUBMIT_SIZE = "submit-large";
    $TEXT_SIZE   = "text-large";
}

$astdb_file_path = $ASTDB_TXT;
$astdb = [];

if (file_exists($astdb_file_path)) {
    $fh = fopen($astdb_file_path, "r");
    if ($fh) {
        if (flock($fh, LOCK_SH)) {
            while (($line = fgets($fh)) !== FALSE) {
                $arr = explode('|', trim($line), 4);
                if (isset($arr[0]) && $arr[0] !== '') {
                    $astdb[$arr[0]] = $arr;
                }
            }
            flock($fh, LOCK_UN);
        }
        fclose($fh);
    }
}

$ini_file_path = get_ini_name($_SESSION['user']);

if (!file_exists($ini_file_path)) {
    die("Couldn't load configuration file: " . htmlspecialchars($ini_file_path));
}

$config = parse_ini_file($ini_file_path, true);
if ($config === false) {
    die("Error parsing configuration file: " . htmlspecialchars($ini_file_path));
}

$valid_nodes = [];
foreach ($passedNodes as $node_input) {
    $node_trimmed = trim($node_input);
    if ($node_trimmed !== '' && isset($config[$node_trimmed])) {
        $valid_nodes[] = $node_trimmed;
    }
}
$nodes = $valid_nodes;

if (empty($nodes)) {
    die ("None of the specified nodes were found in the configuration file: " . htmlspecialchars($ini_file_path));
}

$parms = implode(',', $nodes);

global $parms, $nodes, $config, $astdb;
global $Show_Detail, $SUBMITTER, $SUBMIT_SIZE, $TEXT_SIZE;
global $Displayed_Nodes, $Display_Count, $Show_All;
global $user;

global $WELCOME_MSG, $WELCOME_MSG_LOGGED, $system_type, $EXTN, $IRLPLOG, $DATABASE_TXT;
global $HAMCLOCK_ENABLED, $HAMCLOCK_URL;

$user = $_SESSION['user'];

$current_session_id = session_id();
global $current_session_id;

include("link_scripts.php");
include("link_ui.php");

?>