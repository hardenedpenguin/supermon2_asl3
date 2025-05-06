<?php
include("session.inc");
include("authusers.php");
include("user_files/global.inc");
include("common.inc");

$SUPERMON_DIR = "/var/www/html/supermon2";
$callsign = isset($CALL) ? htmlspecialchars($CALL) : 'System';
$user_files_dir_name = isset($USERFILES) ? $USERFILES : 'user_files';
$user_files_path = $SUPERMON_DIR . '/' . $user_files_dir_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $callsign; ?> - Configuration File Editor</title>
    <link type="text/css" rel="stylesheet" href="/supermon2/supermon.css">
    <style>
        body { background-color: black; color: white; font-family: sans-serif; margin: 20px; }
        h2, p, label, b { color: white; }
        h2 i { color: white; }
        a { color: #6495ED; text-decoration: none; }
        a:hover { color: #87CEFA; text-decoration: underline; }
        hr { border: 0; height: 1px; background-image: linear-gradient(to right, rgba(255, 255, 255, 0), rgba(255, 255, 255, 0.75), rgba(255, 255, 255, 0)); }
        .submit-large { padding: 8px 15px; font-size: 1em; cursor: pointer; border-radius: 5px; transition: background-color 0.2s ease, border-color 0.2s ease; border: 1px solid #0056b3; margin-right: 10px; }
        select.submit-large { background-color: #007bff; color: white; padding: 8px 5px; min-width: 300px; }
        select.submit-large option { background-color: #ffffff; color: #000000; }
        input.submit-large[type="submit"], input.submit-large[type="button"] { background-color: #007bff; color: white; }
        input.submit-large[type="submit"]:hover, input.submit-large[type="button"]:hover { background-color: #0056b3; border-color: #007bff; }
        p, h2, form, label { font-size: 16px; }
        .editor-note { font-size: 0.9em; color: #cccccc; margin-top: 15px; display: inline-block; }
    </style>
</head>
<body>

<?php
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && get_user_auth("CFGEDUSER")) {
?>

    <h2><i><?php echo $callsign; ?></i> - AllStar Link / IRLP / EchoLink - Configuration File Editor</h2>
    <p><b>Please use caution when editing files, misconfiguration can cause problems!</b></p>
    <hr>

    <form name="REFRESH" method="POST" action="configeditor.php" style="margin-bottom: 20px;">
        <input name="refresh" tabindex="50" class="submit-large" type="submit" value="Refresh List">
        <input type="button" class="submit-large" value="Close Window" onclick="self.close()">
    </form>

    <form action="edit.php" method="post" name="select">
        <label for="file_select">Select configuration file to edit:</label><br>
        <select name="file" id="file_select" class="submit-large">
            <?php
            function add_config_option($filepath, $description, $check_writable = false) {
                $exists = file_exists($filepath);
                $is_allowed = (
                    strpos($filepath, '/etc/') === 0 ||
                    strpos($filepath, '/home/irlp/') === 0 ||
                    strpos($filepath, '/usr/local/') === 0 ||
                    strpos($filepath, $GLOBALS['SUPERMON_DIR']) === 0 ||
                    strpos($filepath, '/var/www/html/lsnodes/') === 0
                );

                if ($exists && $is_allowed) {
                    $is_supermon_file = strpos($filepath, $GLOBALS['SUPERMON_DIR']) === 0;
                    if ($check_writable && !$is_supermon_file && !is_writable($filepath)) {
                         return;
                    }
                    echo "<option value=\"" . htmlspecialchars($filepath) . "\">" . htmlspecialchars($description) . "</option>\n";
                }
            }

            echo "<optgroup label=\"Supermon Files\">";
            add_config_option("$user_files_path/authini.inc", "Supermon - $user_files_dir_name/authini.inc");
            add_config_option("$user_files_path/authusers.inc", "Supermon - $user_files_dir_name/authusers.inc");
            add_config_option("$user_files_path/cntrlini.inc", "Supermon - $user_files_dir_name/cntrlini.inc");
            add_config_option("$user_files_path/cntrlnolog.ini", "Supermon - $user_files_dir_name/cntrlnolog.ini");
            add_config_option("$user_files_path/favini.inc", "Supermon - $user_files_dir_name/favini.inc");
            add_config_option("$user_files_path/favnolog.ini", "Supermon - $user_files_dir_name/favnolog.ini");
            add_config_option("$user_files_path/global.inc", "Supermon - $user_files_dir_name/global.inc");
            add_config_option("$user_files_path/nolog.ini", "Supermon - $user_files_dir_name/nolog.ini");
            add_config_option("$user_files_path/allmon.ini", "Supermon - $user_files_dir_name/allmon.ini");
            add_config_option("$user_files_path/favorites.ini", "Supermon - $user_files_dir_name/favorites.ini");
            add_config_option("$user_files_path/controlpanel.ini", "Supermon - $user_files_dir_name/controlpanel.ini");
            add_config_option("$SUPERMON_DIR/supermon.css", "Supermon - supermon.css");
            add_config_option("$SUPERMON_DIR/style.css", "Supermon - style.css");
            add_config_option("$SUPERMON_DIR/astlookup.css", "Supermon - astlookup.css");
            add_config_option("$SUPERMON_DIR/privatenodes.txt", "Supermon - privatenodes.txt");
            echo "</optgroup>";

            echo "<optgroup label=\"AllStar / Asterisk Files\">";
            add_config_option("/etc/asterisk/http.conf", "AllStar - http.conf");
            add_config_option("/etc/asterisk/rpt.conf", "AllStar - rpt.conf");
            add_config_option("/etc/asterisk/custom/iax.conf", "AllStar - iax.conf");
            add_config_option("/etc/asterisk/custom/extensions.conf", "AllStar - extensions.conf");
            add_config_option("/etc/asterisk/dnsmgr.conf", "AllStar - dnsmgr.conf");
            add_config_option("/etc/asterisk/voter.conf", "AllStar - voter.conf");
            add_config_option("/etc/asterisk/manager.conf", "AllStar - manager.conf");
            add_config_option("/etc/asterisk/asterisk.conf", "AllStar - asterisk.conf");
            add_config_option("/etc/asterisk/modules.conf", "AllStar - modules.conf");
            add_config_option("/etc/asterisk/logger.conf", "AllStar - logger.conf");
            add_config_option("/etc/asterisk/usbradio.conf", "AllStar - usbradio.conf");
            add_config_option("/etc/asterisk/simpleusb.conf", "AllStar - simpleusb.conf");
            echo "</optgroup>";

            echo "<optgroup label=\"System Files\">";
            add_config_option("/etc/wpa_supplicant/wpa_supplicant_custom-wlan0.conf", "System - wpa_supplicant_custom-wlan0.conf");
            echo "</optgroup>";

            echo "<optgroup label=\"IRLP Files\">";
            add_config_option("/etc/asterisk/irlp.conf", "AllStar Linking - irlp.conf");
            add_config_option("/home/irlp/custom/custom_decode", "IRLP - custom_decode");
            add_config_option("/home/irlp/custom/custom.crons", "IRLP - custom.crons");
            $irlp_crons_path = "/home/irlp/noupdate/scripts/irlp.crons";
            if (!file_exists($irlp_crons_path)) {
                $irlp_crons_path = "/home/irlp/scripts/irlp.crons";
            }
            add_config_option($irlp_crons_path, "IRLP - irlp.crons");
            add_config_option("/home/irlp/custom/lockout_list", "IRLP - lockout_list");
            add_config_option("/home/irlp/custom/timing", "IRLP - timing");
            add_config_option("/home/irlp/custom/timeoutvalue", "IRLP - timeoutvalue");
            echo "</optgroup>";

            echo "<optgroup label=\"EchoLink Files\">";
            add_config_option("/etc/asterisk/echolink.conf", "EchoLink - echolink.conf");
            echo "</optgroup>";

            echo "<optgroup label=\"AutoSky Files\">";
            add_config_option("/usr/local/bin/AUTOSKY/AutoSky.ini", "AutoSky - AutoSky.ini");
            $autosky_log = "/usr/local/bin/AUTOSKY/AutoSky-log.txt";
            if (file_exists($autosky_log) && filesize($autosky_log) > 0) {
                add_config_option($autosky_log, "AutoSky - AutoSky-log.txt (View Only Recommended)");
            }
            echo "</optgroup>";
            ?>
        </select>
        <input name="Submit" type="submit" class="submit-large" value="Edit Selected File">
        <span class="editor-note">(Note: File editor uses standard black-on-white theme)</span>
    </form>

<?php
} else {
    echo "<h3>ERROR: You must login with sufficient privileges to use the 'Configuration Editor' tool!</h3>";
    echo "<p><a href=\"index.php\">Return to Login</a></p>";
}
?>
</body>
</html>
