<?php
/**
 * Control Panel for AllStar Nodes.
 *
 * This script provides a web interface for sending commands to a specific AllStarLink node.
 * It authenticates users, reads configuration files, and allows users to select and execute predefined commands.
 *
 * @package SuperMon
 */

// Include necessary files for session management, global settings, common functions,
// user authentication, and INI file authentication.
include("session.inc");
include("user_files/global.inc");
include("common.inc");
include("authusers.php");
include("authini.php");

// Check if the user is logged in and has the necessary authorization ('CTRLUSER').
// If not, display an error message and terminate the script.
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("CTRLUSER"))) {
    die("<br><h3>ERROR: You Must login to use the 'Control Panel' function!</h3>");
}

// Get the node number from the GET request or the POST request.
// Strip any HTML tags and trim whitespace for security.
$node = @trim(strip_tags($_GET['node']));
$localnode = @trim(strip_tags($_POST['localnode']));

// If a node number was submitted via POST, use that value.
if ($localnode !== '') {
    $node = $localnode;
}

// Ensure the provided node number is numeric. If not, display an error and terminate.
if (!is_numeric($node)) {
    die("Please provide a properly formated URI. (ie controlpanel.php?node=1234)");
}

// Set the title of the web page, including the current node number.
$title = "AllStar $node Control Panel";

// If the user is logged in, proceed with loading configurations.
if ($_SESSION['sm61loggedin'] === true) {

    // Get the path to the user-specific allmon INI file based on their username.
    $SUPINI = get_ini_name($_SESSION['user']);

    // Read the allmon INI file. If it doesn't exist, display an error and terminate.
    if (!file_exists("$SUPINI")) {
        die("Couldn't load file $SUPINI.\n");
    }
    $allmonConfig = parse_ini_file("$SUPINI", true);

    // Read the controlpanel INI file, which contains the command definitions.
    // If it doesn't exist, display an error and terminate.
    if (!file_exists("$USERFILES/controlpanel.ini")) {
        die("Couldn't load file controlpanel.ini.\n");
    }
    $cpConfig = parse_ini_file("$USERFILES/controlpanel.ini", true);

    // Initialize an array to store the combined control panel commands.
    // Start by including the commands defined in the '[general]' section of controlpanel.ini.
    $cpCommands = $cpConfig['general'];

    // If there's a section in controlpanel.ini specific to the current node number,
    // merge its 'labels' and 'cmds' with the general commands. This allows for
    // node-specific control panel options.
    if (isset($cpConfig[$node])) {
        foreach ($cpConfig[$node] as $type => $arr) {
            if ($type == 'labels') {
                foreach ($arr as $label) {
                    $cpCommands['labels'][] = $label;
                }
            } elseif ($type == 'cmds') {
                foreach ($arr as $cmd) {
                    $cpCommands['cmds'][] = $cmd;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="generator" content="By hand with a text editor">
    <meta name="description" content="AllStar Control Panel">
    <meta name="keywords" content="allstar monitor, app_rpt, asterisk">
    <meta name="author" content="Tim Sawyer, WD6AWP">
    <meta name="mods" content="New features, IRLP capability, Paul Aidukas, KN2R">
    <link type="text/css" rel="stylesheet" href="supermon.css">
    <link type="text/css" rel="stylesheet" href="js/jquery-ui.css">
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>

    <script src="js/alertify.min.js"></script>
    <link rel="stylesheet" href="js/alertify.core.css"/>
    <link rel="stylesheet" href="js/alertify.default.css" id="toggleCSS"/>

    <script>
        $(document).ready(function () {

            <?php if ($_SESSION['sm61loggedin'] !== true) { ?>
            // If the user is not logged in, display an alert message.
            alert('Must login to use the Control Panel.');

            <?php } else { ?>
            // If the user is logged in, show the main control panel div.
            $("#cpMain").show();

            <?php } ?>

            // Event handler for when the 'Execute' button is clicked.
            $('#cpExecute').click(function () {
                // Get the local node number from the hidden input field.
                var localNode = $('#localnode').val();
                // Get the selected command from the dropdown list.
                var cpCommand = $('#cpSelect').val();

                // Perform an AJAX GET request to the 'controlserver.php' script.
                // Pass the node number and the selected command as parameters.
                $.get('controlserver.php?node=' + localNode + '&cmd=' + cpCommand, function (data) {
                    // When the AJAX request is successful, display the returned data
                    // as a success message using the alertify library.
                    alertify.success(data);
                });
            });
        });
    </script>
</head>
<body>
<div id="header" style="background-image: url(<?php echo $BACKGROUND; ?>); background-color:<?php echo $BACKGROUND_COLOR; ?>; height: <?php echo $BACKGROUND_HEIGHT; ?> ">
    <div id="headerTitle" class="headerTitle-large"><i><?php echo "$CALL - $TITLE_LOGGED"; ?></i></div>
    <div id="header4Tag" class="header4Tag-large"><i><?php echo $title ?></i></div>
    <div id="header2Tag" class="header2Tag-large"><i><?php echo $TITLE3; ?></i></div>
    <div id="headerImg"><a href="https://www.allstarlink.org" target="_blank"><img src="allstarlink.jpg" width="70%" alt="Allstar Logo"></a></div>
</div>
<br>
<div id="cpMain">
    <center>Sending Command to node <?php echo $node ?></center>
    <br>
    Control command (select one): <select name="cpSelection" class="submit-large" id="cpSelect">
        <?php
        // Loop through the loaded command labels and create options for the dropdown list.
        for ($i = 0; $i < count($cpCommands['labels']); $i++) {
            // The value of each option is the actual command, and the displayed text is the label.
            print "<option value=\"" . $cpCommands['cmds'][$i] . "\">" . $cpCommands['labels'][$i] . "</option>\n";
        }
        ?>
    </select>
    <input type="hidden" id="localnode" value="<?php echo $node ?>">
    <input type="button" class="submit-large" value="Execute" id="cpExecute">
    <br/><br>
    <div id="cpResult">
        </div>
</div>
<br>
<div style="position:absolute; bottom:0px; left:30%;">
    <center>
        <input type="button" class="submit-large" Value="Close Window" onclick="self.close()">
        <br><br>
        <?php include "footer.inc"; // Include the footer file. ?>
    </center>
</div>
</body>
</html>
