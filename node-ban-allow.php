<?php

include("session.inc");
include('amifunctions.inc');
include("common.inc");
include("authusers.php");
include("authini.php");

// Ensure user is logged in and has the necessary permissions
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("BANUSER"))) {
    if (isset($fp) && $fp) {
        AMIlogoff($fp);
    }
    die("<br><h3 style='color: white; background-color: red; padding: 10px; text-align: center;'>ERROR: You Must login to use the 'Restrict' function!</h3>");
}

// Sanitize GET parameters
$Node = trim(strip_tags($_GET['ban-node']));
$localnode = @trim(strip_tags($_GET['localnode']));

// Determine and validate INI file path
$SUPINI = get_ini_name($_SESSION['user']);
if (!file_exists("$SUPINI")) {
    die("<p style='color: white; background-color: darkred; padding: 10px; border: 1px solid red; text-align: center;'>Couldn't load configuration file: " . htmlspecialchars($SUPINI) . "</p>");
}

// Parse INI file
$config = parse_ini_file("$SUPINI", true);
if ($config === false) {
    die("<p style='color: white; background-color: darkred; padding: 10px; border: 1px solid red; text-align: center;'>Error parsing configuration file: " . htmlspecialchars($SUPINI) . "</p>");
}

// Check if the specified node exists in the configuration
if (!isset($config[$localnode])) {
    die("<p style='color: white; background-color: darkred; padding: 10px; border: 1px solid red; text-align: center;'>Node " . htmlspecialchars($localnode) . " is not defined in " . htmlspecialchars($SUPINI) . " file.</p>");
}

// Establish Asterisk Manager Interface (AMI) connection
if (($fp = AMIconnect($config[$localnode]['host'])) === FALSE) {
    die("<p style='color: white; background-color: darkred; padding: 10px; border: 1px solid red; text-align: center;'>Could not connect to Asterisk Manager on host: " . htmlspecialchars($config[$localnode]['host']) . "</p>");
}

// Login to Asterisk Manager
if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
    AMIlogoff($fp);
    die("<p style='color: white; background-color: darkred; padding: 10px; border: 1px solid red; text-align: center;'>Could not login to Asterisk Manager.</p>");
}

function sendCmdtAMI($fp, $localnode, $cmd)
{
    AMIcommand($fp, $cmd);
}

function getDataAMI($fp, $localnode, $cmd)
{
    $response = AMIcommand($fp, $cmd);
    $response = str_replace('--END COMMAND--', '', $response);
    return trim($response);
}

$feedback_message = ''; // Reset feedback message

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["whiteblack"]) && ($_POST["whiteblack"] != "")) {
    $whiteblack = filter_input(INPUT_POST, 'whiteblack', FILTER_SANITIZE_STRING);
    $node_to_modify = filter_input(INPUT_POST, 'node', FILTER_SANITIZE_NUMBER_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $deleteadd = filter_input(INPUT_POST, 'deleteadd', FILTER_SANITIZE_STRING);

    if ($node_to_modify && preg_match('/^\d{1,7}$/', $node_to_modify) && ($whiteblack === 'whitelist' || $whiteblack === 'blacklist') && ($deleteadd === 'add' || $deleteadd === 'delete')) {
        $DBname = ($whiteblack === "whitelist") ? "whitelist" : "blacklist";

        if ($deleteadd === "add") {
            $cmd_action = "put";
            $escaped_comment = addslashes($comment);
            $ami_cmd = "database $cmd_action $DBname $node_to_modify \"$escaped_comment\"";
            sendCmdtAMI($fp, $localnode, $ami_cmd);
            // Use CSS class for feedback
            $feedback_message = "<p class='feedback-success'>Added/Updated node $node_to_modify in $DBname.</p>";
        } else {
            $cmd_action = "del";
            $ami_cmd = "database $cmd_action $DBname $node_to_modify";
            sendCmdtAMI($fp, $localnode, $ami_cmd);
             // Use CSS class for feedback
            $feedback_message = "<p class='feedback-notice'>Deleted node $node_to_modify from $DBname.</p>";
        }
        $Node = $node_to_modify; // Update $Node for the input field

    } else {
        if (!$node_to_modify || !preg_match('/^\d{1,7}$/', $node_to_modify)) {
             // Use CSS class for feedback
             $feedback_message = "<p class='feedback-error'>Error: Invalid Node number provided. Please enter digits only (max 7).</p>";
        } else {
             // Use CSS class for feedback
             $feedback_message = "<p class='feedback-error'>Error: Invalid form data submitted.</p>";
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Allow/Restrict AllStar Nodes</title>
    <!-- Include existing CSS first -->
    <link type="text/css" rel="stylesheet" href="supermon.css">
    <!-- Include our dark theme CSS AFTER supermon.css -->
    <link type="text/css" rel="stylesheet" href="node-ban-allow-dark.css">
    <!-- NO <style> block here anymore -->
</head>
<body><!-- Removed inline body style -->

<p class="main-title">
    Allow/Restrict AllStar Nodes at node <?php echo htmlspecialchars($localnode); ?>
</p>

<center>
    <?php echo $feedback_message; // Display feedback using classes defined in CSS ?>

    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?ban-node=<?php echo htmlspecialchars($Node); ?>&localnode=<?php echo htmlspecialchars($localnode); ?>" method="post">
        <table>
            <!-- Row 1: White/Black list selection -->
            <tr>
                <td colspan="2" style="padding-bottom: 15px;">
                    <input type="radio" id="rb_blacklist" name="whiteblack" value="blacklist" <?php echo (!isset($_POST['whiteblack']) || $_POST['whiteblack'] === 'blacklist') ? 'checked' : ''; ?>>
                    <label for="rb_blacklist">Restricted (Blacklist)</label>

                    <input type="radio" id="rb_whitelist" name="whiteblack" value="whitelist" <?php echo (isset($_POST['whiteblack']) && $_POST['whiteblack'] === 'whitelist') ? 'checked' : ''; ?>>
                    <label for="rb_whitelist">Allowed (Whitelist)</label>
                </td>
            </tr>
            <!-- Row 2: Node and Comment Input -->
            <tr>
                <td>
                    <label for="node_input">Node Number:</label><br>
                    <input type="text" id="node_input" name="node" value="<?php echo htmlspecialchars($Node); ?>" maxlength="7" size="10" pattern="\d{1,7}" title="Enter node number (1-7 digits)" required>
                </td>
                <td>
                    <label for="comment_input">Comment:</label><br>
                    <input type="text" id="comment_input" name="comment" maxlength="30" size="30" value="<?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?>">
                </td>
            </tr>
            <!-- Row 3: Add/Delete selection -->
            <tr>
                <td colspan="2" style="padding-top: 5px; padding-bottom: 20px;">
                    <input type="radio" id="rb_add" name="deleteadd" value="add" <?php echo (!isset($_POST['deleteadd']) || $_POST['deleteadd'] === 'add') ? 'checked' : ''; ?>>
                    <label for="rb_add">Add / Update</label>

                    <input type="radio" id="rb_delete" name="deleteadd" value="delete" <?php echo (isset($_POST['deleteadd']) && $_POST['deleteadd'] === 'delete') ? 'checked' : ''; ?>>
                    <label for="rb_delete">Delete</label>
                </td>
            </tr>
             <!-- Row 4: Submit/Close Buttons -->
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 15px;">
                    <!-- Buttons use .submit-large class styled in CSS -->
                    <input type="submit" class="submit-large" value="Update List">
                       
                    <input type="button" class="submit-large" value="Close Window" onclick="window.close(); return false;">
                </td>
            </tr>
            <!-- Row 5: Blacklist Display -->
            <tr>
                <td colspan="2">
                    <b>Current Nodes in Restricted (Blacklist):</b>
                    <?php
                    $blacklist_data = getDataAMI($fp, $localnode, "database show blacklist");
                    if (empty($blacklist_data) || strpos($blacklist_data, 'results found') !== false || (strpos($blacklist_data, 'Database entries') !== false && strpos($blacklist_data, ': 0') !== false)) {
                        // Use class for styling 'NONE'
                        echo "<p class='none-message'>--- NONE ---</p>";
                    } else {
                        $blacklist_data = preg_replace('/^\/BL\/(\d+)\s+:\s*(.*)/m', "Node: $1   Comment: $2", $blacklist_data);
                        $blacklist_data = preg_replace('/:\s+/', ': ', $blacklist_data);
                        $blacklist_data = preg_replace('/^\s+/m', '', $blacklist_data);
                        // <pre> block is styled by CSS
                        echo "<pre>" . htmlspecialchars($blacklist_data) . "</pre>";
                    }
                    ?>
                </td>
            </tr>
            <!-- Row 6: Whitelist Display -->
            <tr>
                <td colspan="2">
                   <b>Current Nodes in Allowed (Whitelist):</b>
                    <?php
                    $whitelist_data = getDataAMI($fp, $localnode, "database show whitelist");
                    if (empty($whitelist_data) || strpos($whitelist_data, 'results found') !== false || (strpos($whitelist_data, 'Database entries') !== false && strpos($whitelist_data, ': 0') !== false)) {
                       // Use class for styling 'NONE'
                       echo "<p class='none-message'>--- NONE ---</p>";
                    } else {
                        $whitelist_data = preg_replace('/^\/WL\/(\d+)\s+:\s*(.*)/m', "Node: $1   Comment: $2", $whitelist_data);
                        $whitelist_data = preg_replace('/:\s+/', ': ', $whitelist_data);
                        $whitelist_data = preg_replace('/^\s+/m', '', $whitelist_data);
                         // <pre> block is styled by CSS
                        echo "<pre>" . htmlspecialchars($whitelist_data) . "</pre>";
                    }
                    ?>
                </td>
            </tr>
            <!-- Row 7: Configuration Note -->
            <tr>
                 <td colspan="2">
                     <!-- Use class for styling the note -->
                     <div class="config-note">
                        <b>Note:</b> White or Blacklist usage must be configured
                        via the `context=` setting within the relevant stanza (e.g., `[iax-friends]`, `[YourNodeGroup]`)
                        in the <b>iax.conf</b> (or potentially <b>extensions.conf</b> depending on setup) file for node <b><?php echo htmlspecialchars($localnode); ?></b> for these lists to be effective.
                    </div>
                </td>
            </tr>
        </table>
    </form>
</center>

<?php
// Log off from Asterisk Manager Interface before ending the script
if (isset($fp) && $fp) {
    AMIlogoff($fp);
}
?>

</body>
</html>
