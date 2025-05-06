<?php
include("session.inc");
include("authusers.php");

//  Check if user is logged in and authorized
if (!isset($_SESSION['sm61loggedin']) || $_SESSION['sm61loggedin'] !== true || !get_user_auth("BUBLUSER")) {
    die("<br><h3>ERROR: You must login to use the 'Bubble Chart' function!</h3>");
}

//  Sanitize user input to prevent XSS
$node = isset($_POST['node']) ? htmlspecialchars(trim(strip_tags($_POST['node'])), ENT_QUOTES, 'UTF-8') : '';
$localnode = isset($_POST['localnode']) ? htmlspecialchars(trim(strip_tags($_POST['localnode'])), ENT_QUOTES, 'UTF-8') : '';

//  Conditionally open window based on whether a node is provided
if ($node === '') {
    echo "<script>window.open('http://stats.allstarlink.org/getstatus.cgi?$localnode');</script>";
} else {
    echo "<b>Opening Bubble Chart for node " . htmlspecialchars($node, ENT_QUOTES, 'UTF-8') . "</b>";
    echo "<script>window.open('http://stats.allstarlink.org/getstatus.cgi?$node');</script>";
}
?>
