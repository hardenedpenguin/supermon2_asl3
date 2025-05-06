<?php
/**
 * simpleusb-control.php
 *
 * This script provides a web interface to view and control the settings
 * of a simpleusb-tune enabled sound device connected to an AllStarLink node.
 * It allows users to adjust various audio and PTT/COS parameters in real-time
 * and optionally save them permanently.
 *
 * It communicates with the Asterisk Manager Interface (AMI) on the target node
 * to retrieve current settings and send update commands.
 */

// --- Includes ---
include("session.inc");
include('amifunctions.inc');
include("common.inc");
include("authusers.php");
include("authini.php");

// --- Authentication Check ---
// Added style for white text on potential black background if die() happens early
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("SUSBUSER"))) {
    die ("<body style='background-color:black; color:white;'><br><h3 style='color:white;'>ERROR: You Must login to supermon to use the 'simpleusb-control' function!</h3></body></html>");
}

// --- Configuration and Connection ---
$localnode = @trim(strip_tags($_GET['localnode']));
$SUPINI = get_ini_name($_SESSION['user']);

if (!file_exists("$SUPINI")) {
    die("<body style='background-color:black; color:white;'><br><h3 style='color:white;'>ERROR: Couldn't load $SUPINI file.</h3></body></html>\n");
}
$config = parse_ini_file("$SUPINI", true);
if (!isset($config[$localnode])) {
     die("<body style='background-color:black; color:white;'><br><h3 style='color:white;'>ERROR: Node $localnode is not in $SUPINI file.</h3></body></html>");
}

$fp = false; // Initialize $fp

if (($fp_check = AMIconnect($config[$localnode]['host'])) === FALSE) {
    die("<body style='background-color:black; color:white;'><br><h3 style='color:white;'>ERROR: Could not connect to Asterisk Manager.</h3></body></html>");
}
$fp = $fp_check;

if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
    if ($fp) {
        AMIlogoff($fp);
    }
    die("<body style='background-color:black; color:white;'><br><h3 style='color:white;'>ERROR: Could not login to Asterisk Manager.</h3></body></html>");
}

// --- AMI Communication Function ---
function getDataAMI($fp, $cmd) {
    if (!$fp || !is_resource($fp)) {
         error_log("simpleusb-control: Invalid AMI connection resource in getDataAMI");
         return "";
    }
    $AMI1 = AMIcommand ($fp, $cmd);
    return $AMI1;
}

// --- Form Display Functions ---
function Disp_Form_Radio ($current, $title, $form_name, $opts, $opt1, $opt1_cmd, $opt2 = "", $opt2_cmd = "", $opt3 = "", $opt3_cmd = "", $opt4 = "", $opt4_cmd = "") {
    $check1 = ""; $check2 = ""; $check3 = ""; $check4 = "";
    switch ($current) {
        case 0: $check1 = "checked"; break;
        case 1: $check2 = "checked"; break;
        case 2: $check3 = "checked"; break;
        case 3: $check4 = "checked"; break;
    }
    // Note: Input labels inherit body color (white), which is good. Bolds also inherit.
    switch ($opts[0]) {
        case 1:
            switch ($opts[1]) {
                case 2:
                    echo "<td><b>$title</b><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
                    echo "      ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2 </td>";
                    break;
                case 3:
                    echo "<td><b>$title</b><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
                    echo "      ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2";
                    echo "   ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt3_cmd\" $check3> $opt3 </td>";
                    break;
                case 4:
                    echo "<td><b>$title</b><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
                    echo "      ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2";
                    echo "   ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt3_cmd\" $check3> $opt3";
                    echo "   ";
                    echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt4_cmd\" $check4> $opt4 </td>";
                    break;
            }
            break;
        case 2:
            echo "<td><b>$title</b></td><td><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
            echo "      ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2 </td>";
            break;
        case 3:
            echo "<td><b>$title</b></td><td><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit-large\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
            echo "      ";
            echo "<input type=\"radio\" class=\"submit-large\" style=\"transform: scale(2); margin-left: 5px;\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2";
            echo "   ";
            echo "<input type=\"radio\" class=\"submit-large\" style=\"transform: scale(2); margin-left: 5px;\" name=\"$form_name\" value=\"$opt3_cmd\" $check3> $opt3 </td>";
            break;
     }
}

function Disp_Device_Form_Radio ($current, $title, $form_name, $opts, $opt1, $opt1_cmd, $opt2 = "", $opt2_cmd = "", $opt3 = "", $opt3_cmd = "", $opt4 = "", $opt4_cmd = "") {
    $check1 = ""; $check2 = ""; $check3 = ""; $check4 = "";
    if ($current == $opt1) { $check1 = "checked"; }
    elseif ($current == $opt2) { $check2 = "checked"; }
    elseif ($current == $opt3) { $check3 = "checked"; }
    else { $check4 = "checked"; }
    switch ($opts) {
        case 2:
            echo "<td><b>$title</b><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2 </td>";
            break;
        case 3:
            echo "<td><b>$title</b><input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1";
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2";
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt3_cmd\" $check3> $opt3 </td>";
            break;
        case 4:
            echo "<td><b>$title</b><input type=\"radio\" class=\"submit\" style=\"transform: scale(2); margin-left: 5px;\" name=\"$form_name\" value=\"$opt1_cmd\" $check1> $opt1"; // Added style
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt2_cmd\" $check2> $opt2";
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt3_cmd\" $check3> $opt3";
            echo "   ";
            echo "<input type=\"radio\" style=\"transform: scale(2); margin-left: 5px;\" class=\"submit\" name=\"$form_name\" value=\"$opt4_cmd\" $check4> $opt4 </td>";
            break;
    }
}

function Disp_Form_Numeric ($current, $title, $form_name, $len, $minval, $maxval, $disabled) {
    if ($disabled == "") {
        echo "<td><b>$title</b>";
        $Submit = "submit";
    } else {
        // Changed disabled color to a lighter gray for visibility on black
        echo "<td>  <span style=\"color: #A0A0A0;\"> $title</span>";
        $Submit = "";
    }
    // Input field text color might need adjusting if browser default isn't white/light
    echo " <input type=\"number\" class=\"$Submit-large\" name=\"$form_name\" maxlength=\"$len\" min=\"$minval\" max=\"$maxval\" value=\"$current\" $disabled style=\"width: 60px; color: black; background-color: #E0E0E0;\" /></td>"; // Added basic input styling
}

// --- Data Processing and Command Functions ---
function check_new_val($item, $loc, $off, $on, $prefix = "") {
    global $fp;
    if ($item === "" || $item == $loc) {
        return;
    }
    if ($prefix !== "") {
        Send_Command($fp, $prefix . $item);
    } else {
        if ($item == 0 && $off !== "") {
            Send_Command($fp, $off);
        } elseif ($item != 0 && $on !== "") {
            Send_Command($fp, $on);
        }
    }
}

function Send_Command($fp, $Command) {
    if ($Command == "j") {
        $_SESSION['WRITE'] = "1";
    }
    $ret = getDataAMI($fp, "susb tune menu-support $Command");
    $ret = str_replace('--END COMMAND--', '', $ret);
    return $ret;
}

function Send_Basic_Command($fp, $Command) {
    $ret = getDataAMI($fp, "$Command");
    $ret = str_replace('--END COMMAND--', '', $ret);
    return $ret;
}

function Align_Title($title) {
    $title .= " -";
    return $title;
}

function state($item) {
    global $j;
    return ($j['selected'][$item] ?? null);
}

function device($stanza) {
    global $j;
    return ($j['devices'][$stanza] ?? null);
}

function hasusb($device) {
    global $j;
    if (isset($j['hasusb'][$device]) && $j['hasusb'][$device] == 1) {
        return "Attached and Configurable";
    } else {
        return "Not Attached and not configurable";
    }
}

function test_input($data) {
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function status_text($status) {
    if ($status == "0") {
        // Explicitly set black text on yellow background for better contrast
        return "<span style=\"background-color: #FFFF00; color: black; padding: 2px 4px;\">'CLEAR'</span>";
    } else {
        // White text on red background should be fine, added padding
        return "<span style=\"background-color: red; color: white; padding: 2px 4px;\">'KEYED'</span>";
    }
}

// --- Main Logic Start ---
$current = Send_Command($fp, "4");
$j = json_decode($current, true);

$device_cur = null; $deemp_loc = null; $preemp_loc = null; $plfilter_loc = null; $dcsfilter_loc = null;
$coskey_loc = null; $rxboost_loc = null; $pttinv_loc = null; $cos_loc = null; $ctcss_loc = null; $echo_loc = null;
$rxlev_loc = null; $rxondly_loc = null; $rxauddly_loc = null; $txleva_loc = null; $txlevb_loc = null; $txdsplev_loc = null;
$pttstatus = null; $coscomposite = null; $txmode_loc = null; $write_loc = "";

if (json_last_error() != JSON_ERROR_NONE) {
    // Ensure error message uses white text on black background
    echo "<!DOCTYPE html><html><head><title>Error</title><link type=\"text/css\" rel=\"stylesheet\" href=\"supermon.css\"></head><body style=\"background-color:black; color:white;\">";
    echo "<br><br><b style='color:white;'> Read Error - is simpleusb enabled on this node? (JSON Error: " . json_last_error_msg() . ")</b>";
    echo "<form action=\"simpleusb-control.php?localnode=" . htmlspecialchars($localnode) . "\" method=\"post\">";
    echo "<p><input type=\"button\" class=\"submit-large\" Value=\"Close Window\" onclick=\"self.close()\"></p>";
    echo "</form>";
    echo "</body></html>";
    if ($fp) {
        AMIlogoff($fp);
    }
    exit;

} else {
    // Try reading initial state
    if (isset($j['selected'])) {
        $device_name_from_state = state('name');
        if (isset($j['devices']) && $device_name_from_state !== null && isset($j['devices'][$device_name_from_state])) {
             $device_cur = $device_name_from_state;
        }
        $deemp_loc = state('deemphasis'); $preemp_loc = state('preemphasis'); $plfilter_loc = state('plfilter');
        $dcsfilter_loc = state('dcsfilter'); $coskey_loc = state('rxtestkeyed'); $rxboost_loc = state('rxboostset');
        $pttinv_loc = state('invertptt'); $cos_loc = state('rxcdtype'); $ctcss_loc = state('rxsdtype');
        $echo_loc = state('echomode'); $rxlev_loc = state('rxmixerset'); $rxondly_loc = state('rxondelay');
        $rxauddly_loc = state('rxaudiodelay'); $txleva_loc = state('txmixaset'); $txlevb_loc = state('txmixbset');
        $txdsplev_loc = state('txdsplevel'); $pttstatus = state('pttstatus'); $coscomposite = state('coscomposite');
        $txmode_loc = state('tx_audio_level_method');
    } else {
        error_log("simpleusb-control: Missing 'selected' key in JSON response for node " . $localnode);
        // $device_cur remains null, error message will show later in HTML.
    }

    // Process Form Submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $device = test_input($_POST["device"] ?? null); $deemp = test_input($_POST["deemp"] ?? null); $preemp = test_input($_POST["preemp"] ?? null);
        $plfilter = test_input($_POST["plfilter"] ?? null); $dcsfilter = test_input($_POST["dcsfilter"] ?? null); $cos = test_input($_POST["cos"] ?? null);
        $ctcss = test_input($_POST["ctcss"] ?? null); $rxondly = test_input($_POST["rxondly"] ?? null); $rxauddly = test_input($_POST["rxauddly"] ?? null);
        $rxlev = test_input($_POST["rxlev"] ?? null); $txleva = test_input($_POST["txleva"] ?? null); $txlevb = test_input($_POST["txlevb"] ?? null);
        $txdsplev = test_input($_POST["txdsplev"] ?? null); $rxboost = test_input($_POST["rxboost"] ?? null); $echo = test_input($_POST["echo"] ?? null);
        $pttinv = test_input($_POST["pttinv"] ?? null); $coskey = test_input($_POST["coskey"] ?? null); $write = test_input($_POST["write"] ?? null);
        $txmode = test_input($_POST["txmode"] ?? null);

        // Handle Device Change
        if ($device !== null && $device !== $device_cur && (!isset($_SESSION['DEVICE']) || $_SESSION['DEVICE'] != $device)) {
            Send_Basic_Command($fp, "susb active " . escapeshellarg($device));
            $_SESSION['DEVICE'] = $device;
            $current = Send_Command($fp, "4");
            $j = json_decode($current, true);
            if (json_last_error() == JSON_ERROR_NONE && isset($j['selected'])) {
                $device_name_from_state = state('name');
                 if ($device_name_from_state !== null && isset($j['devices'][$device_name_from_state])) {
                     $device_cur = $device_name_from_state;
                 } else { $device_cur = null; }
                $deemp_loc = state('deemphasis'); $preemp_loc = state('preemphasis'); $plfilter_loc = state('plfilter'); $dcsfilter_loc = state('dcsfilter');
                $coskey_loc = state('rxtestkeyed'); $rxboost_loc = state('rxboostset'); $pttinv_loc = state('invertptt'); $cos_loc = state('rxcdtype');
                $ctcss_loc = state('rxsdtype'); $echo_loc = state('echomode'); $rxlev_loc = state('rxmixerset'); $rxondly_loc = state('rxondelay');
                $rxauddly_loc = state('rxaudiodelay'); $txleva_loc = state('txmixaset'); $txlevb_loc = state('txmixbset'); $txdsplev_loc = state('txdsplevel');
                $pttstatus = state('pttstatus'); $coscomposite = state('coscomposite'); $txmode_loc = state('tx_audio_level_method');
            } else {
                 // Display error within the main page structure
                 echo "<p style='color:red; font-weight:bold;'>Error reading settings after device change. (JSON Error: " . json_last_error_msg() . ")</p>";
                 $device_cur = null; // Indicate error state
            }
        // Handle Other Setting Changes
        } elseif ($device_cur) {
            if (!isset($_SESSION['DEVICE']) || $_SESSION['DEVICE'] !== $device_cur ) {
                $_SESSION['DEVICE'] = $device_cur;
                Send_Basic_Command($fp, "susb active " . escapeshellarg($device_cur));
            }
            check_new_val($deemp, $deemp_loc, "d", "D"); check_new_val($preemp, $preemp_loc, "p", "P"); check_new_val($plfilter, $plfilter_loc, "r", "R");
            check_new_val($dcsfilter, $dcsfilter_loc, "s", "S"); check_new_val($rxboost, $rxboost_loc, "x", "X"); check_new_val($echo, $echo_loc, "e", "E");
            check_new_val($pttinv, $pttinv_loc, "i", "I"); check_new_val($coskey, $coskey_loc, "k", "K"); check_new_val($cos, $cos_loc, "", "", "m");
            check_new_val($ctcss, $ctcss_loc, "", "", "M"); check_new_val($rxondly, $rxondly_loc, "", "", "t"); check_new_val($rxauddly, $rxauddly_loc, "", "", "T");
            check_new_val($rxlev, $rxlev_loc, "", "", "c"); check_new_val($txleva, $txleva_loc, "", "", "f"); check_new_val($txlevb, $txlevb_loc, "", "", "g");
            check_new_val($txdsplev, $txdsplev_loc, "", "", "h"); check_new_val($txmode, $txmode_loc, "", "", "n"); check_new_val($write, $write_loc, "", "j");

            usleep(200000); // Delay

            // Refresh state after updates
             $current = Send_Command($fp, "4");
             $j = json_decode($current, true);
             if (json_last_error() == JSON_ERROR_NONE && isset($j['selected'])) {
                 $device_name_from_state = state('name');
                 if ($device_name_from_state !== null && isset($j['devices'][$device_name_from_state])) {
                     $device_cur = $device_name_from_state;
                 } else { $device_cur = null; }
                 $deemp_loc = state('deemphasis'); $preemp_loc = state('preemphasis'); $plfilter_loc = state('plfilter'); $dcsfilter_loc = state('dcsfilter');
                 $coskey_loc = state('rxtestkeyed'); $rxboost_loc = state('rxboostset'); $pttinv_loc = state('invertptt'); $cos_loc = state('rxcdtype');
                 $ctcss_loc = state('rxsdtype'); $echo_loc = state('echomode'); $rxlev_loc = state('rxmixerset'); $rxondly_loc = state('rxondelay');
                 $rxauddly_loc = state('rxaudiodelay'); $txleva_loc = state('txmixaset'); $txlevb_loc = state('txmixbset'); $txdsplev_loc = state('txdsplevel');
                 $pttstatus = state('pttstatus'); $coscomposite = state('coscomposite'); $txmode_loc = state('tx_audio_level_method');
             } else {
                 // Display error within the main page structure
                 echo "<p style='color:red; font-weight:bold;'>Error re-reading settings after update. (JSON Error: " . json_last_error_msg() . ")</p>";
                 $device_cur = null;
             }
        }
    // Initial Page Load
    } else {
        if ($device_cur) {
            Send_Basic_Command($fp, "susb active " . escapeshellarg($device_cur));
            $_SESSION['DEVICE'] = $device_cur;
        }
    }

// --- HTML Output Start ---
?>
<!DOCTYPE html>
<html>
<head>
<link type="text/css" rel="stylesheet" href="supermon.css">
<!-- Add basic style overrides for dark theme -->
<style>
    /* Ensure high-contrast links if supermon.css doesn't handle it */
    a:link, a:visited { color: #9EF; } /* Light blue links */
    a:hover, a:active { color: #FFF; } /* White on hover */
    /* Style buttons for dark background if needed */
    .submit-large, .submit {
        /* Consider adding background/border/text color for buttons */
        /* Example:
        background-color: #333;
        color: white;
        border: 1px solid #777;
         */
    }
    /* Ensure table cells have borders visible on black */
    .simpUSB td, .simpUSB th {
        border: 1px solid #444; /* Dark gray border */
        padding: 5px; /* Add some padding */
    }
    .simpUSB {
       border-collapse: collapse; /* Ensure borders combine cleanly */
       margin: 1em auto; /* Center tables */
       background-color: #181818; /* Slightly off-black table background */
    }

</style>
<title>SimpleUSB Control - Node <?php echo htmlspecialchars($localnode); ?></title>
</head>
<body style="background-color: black; color: white;">

<p style="text-align:center; font-size: 1.5em; margin-bottom:0;">
    <b>View/Control simpleusb on node <?php echo htmlspecialchars($localnode); ?></b>
</p>

<center>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?localnode=<?php echo htmlspecialchars($localnode); ?>" method="post">
    <p>
        <input type="submit" class="submit-large" value="Update">
         
        <input type="button" class="submit-large" Value="Close Window" onclick="self.close()">
    </p>

    <?php if ($device_cur): // Display controls only if a device is active ?>
        <!-- Device Info Table -->
        <table class="simpUSB">
            <?php
            $Device_Status = hasusb($device_cur);
            echo "<tr align=\"center\"><td><p style=\"font-size:1.1em; margin: 5px;\"><b>Current Device: " . htmlspecialchars($device_cur) . "</b></p></td></tr>"; // Added margin
            echo "<tr align=\"center\"><td><p style=\"font-size:1.1em; margin: 5px;\"><b>Status: " . htmlspecialchars($Device_Status) . "</b></p></td></tr>"; // Added margin

            $keys = (isset($j['devices']) && is_array($j['devices'])) ? array_keys($j['devices']) : [];
            $num_devices = count($keys);

            echo "<tr align=\"center\">";
            if ($num_devices > 1) {
                $key0 = $keys[0] ?? null; $key1 = $keys[1] ?? null; $key2 = $keys[2] ?? null; $key3 = $keys[3] ?? null;
                if ($key0 === null) { echo "<td>Error: Invalid device list.</td>"; }
                else {
                    // Title paragraph needs margin adjusted if needed
                    $title_p = "<p style=\"font-size:1.1em; margin: 5px 0;\">Select Device:</p>";
                    switch ($num_devices) {
                        case 2: if ($key1 !== null) Disp_Device_Form_Radio($device_cur, $title_p, "device", 2, $key0, $key0, $key1, $key1); break;
                        case 3: if ($key1 !== null && $key2 !== null) Disp_Device_Form_Radio($device_cur, $title_p, "device", 3, $key0, $key0, $key1, $key1, $key2, $key2); break;
                        case 4: if ($key1 !== null && $key2 !== null && $key3 !== null) Disp_Device_Form_Radio($device_cur, $title_p, "device", 4, $key0, $key0, $key1, $key1, $key2, $key2, $key3, $key3); break;
                    }
                }
            } elseif ($num_devices === 1 && isset($keys[0])) {
                 echo "<td><input type=\"hidden\" name=\"device\" value=\"" . htmlspecialchars($keys[0]) . "\"></td>";
            } else { echo "<td>No other configurable devices found.</td>"; }
            echo "</tr>";
            ?>
        </table>

        <!-- On/Off Settings Table -->
        <table cellspacing="0" cellpadding="0" class="simpUSB">
             <tr> <?php Disp_Form_Radio($deemp_loc, Align_Title("DE-EMPHASIS"), "deemp", "2", "OFF", "0", "ON", "1"); ?> <?php Disp_Form_Radio($preemp_loc, Align_Title("PRE-EMPHASIS"), "preemp", "2", "OFF", "0", "ON", "1"); ?> </tr>
             <tr> <?php Disp_Form_Radio($plfilter_loc, Align_Title("PL FILTER"), "plfilter", "2", "OFF", "0", "ON", "1"); ?> <?php Disp_Form_Radio($dcsfilter_loc, Align_Title("DCS FILTER"), "dcsfilter", "2", "OFF", "0", "ON", "1"); ?> </tr>
             <tr> <?php Disp_Form_Radio($echo_loc, Align_Title("ECHO BACK"), "echo", "2", "OFF", "0", "ON", "1"); ?> <?php Disp_Form_Radio($coskey_loc, Align_Title("KEY COS"), "coskey", "2", "OFF", "0", "ON", "1"); ?> </tr>
             <tr> <?php Disp_Form_Radio($rxboost_loc, Align_Title("RX BOOST"), "rxboost", "2", "OFF", "0", "ON", "1"); ?> <?php Disp_Form_Radio($pttinv_loc, Align_Title("INVERT PTT"), "pttinv", "2", "OFF", "0", "ON", "1"); ?> </tr>
        </table>

        <!-- Mode Settings Table -->
        <table class="simpUSB">
            <tr align="center"> <?php Disp_Form_Radio($cos_loc, "COS    -    ", "cos", array(1, 3), "None", "0", "USB", "1", "USB-invert", "2"); ?> </tr>
            <tr align="center"> <?php Disp_Form_Radio($ctcss_loc, "CTCSS -   ", "ctcss", array(1, 3), "None", "0", "USB", "1", "USB-invert", "2"); ?> </tr>
            <tr align="center"> <?php Disp_Form_Radio($txmode_loc, "TX Audio Mode -   ", "txmode", array(1, 2), "Log", "0", "Linear", "1"); ?> </tr>
        </table>

        <!-- Numeric Settings Table -->
        <table class="simpUSB">
            <tr> <?php Disp_Form_Numeric($rxlev_loc, "RX Level    -", "rxlev", "3", "0", "999", ""); ?> <?php Disp_Form_Numeric($rxondly_loc, "rxondelay   -", "rxondly", "4", "-300", "300", ""); ?> <?php Disp_Form_Numeric($rxauddly_loc, "rxaudiodelay  -", "rxauddly", "3", "0", "26", ""); ?> </tr>
            <tr> <?php $txb_disabled = ""; $dsp_disabled = ""; ?> <?php Disp_Form_Numeric($txleva_loc, "TX Level A -", "txleva", "3", "0", "999", ""); ?> <?php Disp_Form_Numeric($txlevb_loc, "TX Level B -", "txlevb", "3", "0", "999", $txb_disabled); ?> <?php Disp_Form_Numeric($txdsplev_loc, "TX DSP Level -", "txdsplev", "3", "800", "999", $dsp_disabled); ?> </tr>
        </table>

        <!-- Status Indicators Table -->
        <?php $cos_stat = status_text($coscomposite); $ptt_stat = status_text($pttstatus); ?>
        <table class="simpUSB" cellspacing="10"> <!-- Cellspacing might conflict with CSS border collapse, review -->
            <tr> <td style="border: none;"><b>COS/CTCSS Composite Status - <?php echo $cos_stat; ?></b></td> <td style="border: none;"><b>PTT Status - <?php echo $ptt_stat; ?></b></td> </tr>
        </table>

        <!-- Write Settings Option Table -->
        <table class="simpUSB">
            <tr> <td><?php Disp_Form_Radio($write_loc, "Permanently Write Settings -   ", "write", array(1, 2), "NO", "0", "YES", "1"); ?></td> </tr>
        </table>

        <!-- Confirmation Message for Write -->
        <?php
        if (isset($_SESSION["WRITE"]) && $_SESSION['WRITE'] == "1") {
            // Added specific styling for confirmation message table/cell if needed
            echo "<table class=\"simpUSB\" style=\"background-color: #003000; border-color: lime;\"><tr><td align=\"center\" style=\"border-color: lime;\"><p style=\"font-size:1.1em; color: lime; margin: 5px;\"><b>";
            echo "Device '" . htmlspecialchars($device_cur) . "' Settings Permanently Saved";
            echo "</b></p></td></tr></table>";
            $_SESSION['WRITE'] = "0";
        }
        ?>
    <?php else: // This 'else' corresponds to 'if ($device_cur)' ?>
        <p style="color: #FF8080; font-weight: bold;">No SimpleUSB device appears to be active or configured, or there was an error reading the device state.</p> <?php // Lighter red for error on black ?>
    <?php endif; // End 'if ($device_cur)' ?>
</form>
</center>

<?php
    // --- Final AMI Logoff ---
    if ($fp) {
        AMIlogoff($fp);
    }
?>
</body>
</html>
<?php
} // End of the main 'else' block (that started after the initial JSON error check).
?>
