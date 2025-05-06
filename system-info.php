<?php

include("session.inc");
include("common.inc");
include("user_files/global.inc");
include("authusers.php");
include("authini.php");
include("favini.php");
include("cntrlini.php");

// Check if the user is logged in (session variable set) AND
// if the logged-in user has the specific permission 'SYSINFUSER' required for this page.
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("SYSINFUSER"))) {
    // If not logged in or not authorized, terminate script execution and show an error.
    die("<br><h3>ERROR: You Must login to use the 'System Info' function!</h3>");
}

// Initialize the detail display setting flag to 0 (summary view)
$Show_Detail = 0;
// Check if the 'display-data' cookie exists and is an array
if (isset($_COOKIE['display-data']) && is_array($_COOKIE['display-data'])) {
    // Loop through the elements of the cookie array
    foreach ($_COOKIE['display-data'] as $name => $value) {
        // Sanitize the cookie key name
        $name = htmlspecialchars($name);
        // Check if this cookie key is the one controlling detailed display
        switch ($name) {
            case "show-detailed":
                // If found, sanitize the value and update the flag
                $Show_Detail = htmlspecialchars($value);
                break;
        }
    }
}

?>

<html>
<head>
    <meta charset="UTF-8"> <!-- Set character encoding for the page -->
    <link type="text/css" rel="stylesheet" href="supermon.css"> <!-- Link the main stylesheet -->
    <script>
        // JavaScript function to refresh the parent/opener window when this popup closes.
        function refreshParent() {
            // Check if an opener window exists and hasn't been closed
            if (window.opener && !window.opener.closed) {
                 try {
                     // Attempt to reload the opener window's location
                     window.opener.location.reload();
                 } catch (e) {
                     // Log any errors during the reload attempt to the console
                     console.error("Error reloading opener window:", e);
                 }
            }
        }
    </script>
    <title>System Info</title> <!-- Set the title for the browser tab/window -->
</head>

<body> 
    <p class="page-title">System Info</p>
    <br> 
    <?php
    // Determine the appropriate CSS class for the main content area based on the $Show_Detail flag
    $info_container_class = ($Show_Detail == 1) ? 'info-container-detailed' : 'info-container-summary';

    // Print the opening tag for the main content div with the determined class
    print "<div class=\"" . htmlspecialchars($info_container_class) . "\">";

    // --- Define Command Paths ---
    // Set paths for external commands, using variables from common.inc if available, otherwise default paths.
    $HOSTNAME_CMD = isset($HOSTNAME) ? $HOSTNAME : '/usr/bin/hostname';
    $AWK_CMD = isset($AWK) ? $AWK : '/usr/bin/awk';
    $DATE_CMD = isset($DATE) ? $DATE : '/usr/bin/date';
    $CAT_CMD = isset($CAT) ? $CAT : '/usr/bin/cat';
    $EGREP_CMD = isset($EGREP) ? $EGREP : '/usr/bin/egrep';
    $SED_CMD = isset($SED) ? $SED : '/usr/bin/sed';
    $GREP_CMD = isset($GREP) ? $GREP : '/usr/bin/grep';
    $HEAD_CMD = isset($HEAD) ? $HEAD : '/usr/bin/head';
    $TAIL_CMD = isset($TAIL) ? $TAIL : '/usr/bin/tail';
    $CURL_CMD = isset($CURL) ? $CURL : '/usr/bin/curl';
    $CUT_CMD = isset($CUT) ? $CUT : '/usr/bin/cut';
    $IFCONFIG_CMD = isset($IFCONFIG) ? $IFCONFIG : '/usr/bin/ip a';
    $UPTIME_CMD = isset($UPTIME) ? $UPTIME : '/usr/bin/uptime';

    // Get hostname (first part before any dot)
    $hostname = exec("$HOSTNAME_CMD | $AWK_CMD -F '.' '{print $1}'");
    // Get the current date and time formatted
    $myday = exec("$DATE_CMD '+%A, %B %e, %Y %Z'");
    // Get Asterisk IAX bind port from iax.conf
    $astport = exec("$CAT_CMD /etc/asterisk/iax.conf | $EGREP_CMD '^bindport' | $SED_CMD 's/bindport= //g'");
    // Get Asterisk Manager Interface (AMI) port from manager.conf
    $mgrport = exec("$CAT_CMD /etc/asterisk/manager.conf | $EGREP_CMD '^port =' | $SED_CMD 's/port = //g'");
    // Get Apache HTTP listen port from ports.conf
    $http_port = exec("$GREP_CMD ^Listen /etc/apache2/ports.conf | $SED_CMD 's/Listen //g'");

    // Initialize IP variables
    $myip = 'N/A'; $mylanip = 'N/A'; $WL = '';
    // Check if WANONLY mode is disabled (meaning we should try to get both Public and LAN IPs)
    if (empty($WANONLY)) {
        // Define the URL for the external IP lookup service
        $ip_source_url = 'https://api.ipify.org';

        // Check if the curl command exists and is executable by the web server user
        if (!empty($CURL_CMD) && is_executable($CURL_CMD)) {
            // Construct the curl command with options for silence, connection timeout, and max time
            $myip_cmd = $CURL_CMD . " -s --connect-timeout 3 --max-time 5 " . escapeshellarg($ip_source_url);
            // Initialize variables for curl output and status
            $ip_output_lines = [];
            $ip_return_status = -1;

            // Execute the curl command
            $potential_ip = exec($myip_cmd, $ip_output_lines, $ip_return_status);

            // Validate the result from curl
            // Check: curl exited successfully (0), output is not empty, and output is a valid IP address format
            if ($ip_return_status === 0 && !empty($potential_ip) && filter_var($potential_ip, FILTER_VALIDATE_IP)) {
                // If valid, assign the trimmed IP to $myip
                $myip = trim($potential_ip);
            } else {
                // If validation fails, set status to Lookup Failed
                $myip = 'Lookup Failed';
            }
        } else {
            // If curl command is not available or executable, set status accordingly
            $myip = 'Lookup Failed (curl not found/executable)';
        }

        // Try getting the IP from the first 'inet' line (usually primary ethernet)
        $mylanip_cmd1 = "$IFCONFIG_CMD | $GREP_CMD inet | $HEAD_CMD -1 | $AWK_CMD '{print $2}'";
        $mylanip = exec($mylanip_cmd1);
        // If the first attempt yielded localhost (127.0.0.1) or was empty, try the *last* 'inet' line
        if ($mylanip == "127.0.0.1" || empty($mylanip)) {
            $mylanip_cmd2 = "$IFCONFIG_CMD | $GREP_CMD inet | $TAIL_CMD -1 | $AWK_CMD '{print $2}'";
            $mylanip = exec($mylanip_cmd2);
            // If this second attempt got a valid non-localhost IP, set the 'W' flag (might be wireless/secondary)
            if ($mylanip != "127.0.0.1" && !empty($mylanip)) {
                $WL = "W";
            } elseif (empty($mylanip)) {
                 // If still no IP found, set status
                 $mylanip = 'Not Found';
            }
        }
    } else { 
        // In WANONLY mode, only try to get the LAN IP and assume it's also the relevant "public" IP for this context.
        $mylanip_cmd = "$IFCONFIG_CMD | $GREP_CMD inet | $HEAD_CMD -1 | $AWK_CMD '{print $2}'";
        $mylanip = exec($mylanip_cmd);
         // If no LAN IP found, set status
         if (empty($mylanip)) { $mylanip = 'Not Found'; }
        // Set public IP ($myip) to be the same as the LAN IP
        $myip = $mylanip;
    }

    // Get configured SSH port from sshd_config (take the last 'Port' directive found)
    $myssh = exec("$CAT_CMD /etc/ssh/sshd_config | $EGREP_CMD '^Port' | $TAIL_CMD -1 | $CUT_CMD -d' ' -f2");
    // If no specific port directive is found, assume the default SSH port 22
    if (empty($myssh)) { $myssh = 'Default (22)'; }

    // Display Supermon version title and date (variables likely from common.inc or global.inc)
    print "Version - " . (isset($TITLE_LOGGED) ? htmlspecialchars($TITLE_LOGGED) : 'N/A') . "<br>";
    print "Date - " . (isset($VERSION_DATE) ? htmlspecialchars($VERSION_DATE) : 'N/A') . "<br>";

    // Display Hostname
    print "Hostname - " . htmlspecialchars($hostname) . "<br>";
    // Display Public IP (as a link to iplog.txt if it exists)
    print "Public IP - <a href=\"custom/iplog.txt\" target=\"_blank\">" . htmlspecialchars($myip) . "</a>";
    // If Public and LAN IPs are different and LAN IP was found, display LAN IP on a new line
    if ($myip != $mylanip && $mylanip !== 'Not Found' && !empty($mylanip)) {
        print " . $WL<br>"; // Include 'W' flag if set
        print "LAN IP - " . htmlspecialchars($mylanip) . "<br>";
    } else {
        // Otherwise, just add a line break after Public IP
        print "<br>";
    }
    // Display configured ports
    print "IAX Port - " . htmlspecialchars($astport) . "<br>";
    print "Asterisk Manager Port - " . htmlspecialchars($mgrport) . "<br>";
    print "SSH Port - " . htmlspecialchars($myssh) . "<br>";
    print "HTTP Port - " . htmlspecialchars($http_port) . "<br><br>"; // Add extra space

    // Get HamVoIP/AllStar base version (if file exists)
    $R1 = exec("head -1 /etc/allstar_version");
    // Get Asterisk version string
    $R2 = exec("/sbin/asterisk -V"); // Ensure asterisk is in path or adjust '/sbin/'
    // Get Linux Kernel version string (attempt to parse from /proc/version)
    $R3 = exec("cat /proc/version | awk -F '[(][g]' '{print $1}'"); // Part before '(gcc...'
    $R4 = exec("cat /proc/version | awk -F '[(][g]' '{print 'g'$2}'"); // Part starting with 'gcc...'

    // Display subheading using CSS class
    print "<p class=\"section-subheader\">AllStar Version Numbers</p>";
    // Display Asterisk and Kernel versions
    print "<b>Asterisk Version:</b><br>" . htmlspecialchars($R2) . "<br>";
    print "<b>Linux Kernel Version:</b><br>" . htmlspecialchars($R3) . htmlspecialchars($R4) . "<br>";
    print "<br>"; // Add space

    // Define the expected directory for user configuration files
    $user_files_dir = isset($USERFILES) ? $USERFILES : 'user_files';
    // Display the full path to the user files directory
    print "ALL user configurable files are in the <b>\"" . htmlspecialchars(getcwd()) . "/" . htmlspecialchars($user_files_dir) . "\"</b> directory.<br><br>";

    // Get the current logged-in username from the session, default to N/A
    $current_user = isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']) : 'N/A';
    // Determine the primary INI file being used (dynamic via get_ini_name or default allmon.ini)
    $current_ini = function_exists('get_ini_name') ? get_ini_name($current_user) : 'allmon.ini';
    // Display the logged-in user and the INI file they are currently using
    print "Logged in as: '<b>" . $current_user . "</b>' using INI file: '<b>" . htmlspecialchars($current_ini) . "</b>'<br>";

    // Determine the INI file used when *not* logged in
    $logged_out_ini = "$user_files_dir/allmon.ini"; // Default
    // Check if specific files exist indicating a separate "logged out" INI configuration
    if (file_exists("$user_files_dir/authini.inc") && file_exists("$user_files_dir/nolog.ini")) {
        $logged_out_ini = "$user_files_dir/nologin.ini"; // Use nologin.ini if found
    }
    // Display the path to the INI file used when logged out
    print "Supermon Logged OUT INI: \"<b>" . htmlspecialchars($logged_out_ini) . "</b>\"<br>";
    print "<br>"; // Add space

    // Check if the selective INI functions are valid/available
    $ini_valid = function_exists('iniValid') && iniValid(); // Main selective INI
    $favini_valid = function_exists('faviniValid') && faviniValid(); // Selective Favorites
    $cntrlini_valid = function_exists('cntrliniValid') && cntrliniValid(); // Selective Control Panel

    // Display status for main selective INI
    if (file_exists("$user_files_dir/authini.inc") && $ini_valid) {
        print "Selective INI based on username: <b>ACTIVE</b><br>";
    } else {
        print "Selective INI based on username: <b>INACTIVE</b> (Using <b>" . htmlspecialchars("$user_files_dir/allmon.ini") . "</b>)<br>";
    }

    // Display status for selective button visibility (authusers.inc)
    if (file_exists("$user_files_dir/authusers.inc")) {
        // Note: Auth rules are often related to the currently loaded main INI
        print "Button selective based on username: <b>ACTIVE</b> (using rules related to '<b>" . htmlspecialchars($current_ini) . "</b>')<br>";
    } else {
        print "Button selective based on username: <b>INACTIVE</b><br>";
    }

    // Display status for selective Favorites INI
    if (file_exists("$user_files_dir/favini.inc") && $favini_valid && function_exists('get_fav_ini_name')) {
        // Get the specific favorites INI for the current user
        $current_fav_ini = get_fav_ini_name($current_user);
        print "Selective Favorites INI based on username: <b>ACTIVE</b> (using <b>\"" . htmlspecialchars($current_fav_ini) . "</b>\")<br>";
    } else {
        // Default favorites INI path
        print "Selective Favorites INI: <b>INACTIVE</b> (using <b>" . htmlspecialchars("$user_files_dir/favorites.ini") . "</b>)<br>";
    }

    // Display status for selective Control Panel INI
    if (file_exists("$user_files_dir/cntrlini.inc") && $cntrlini_valid && function_exists('get_cntrl_ini_name')) {
        // Get the specific control panel INI for the current user
        $current_cntrl_ini = get_cntrl_ini_name($current_user);
        print "Selective Control Panel INI based on username: <b>ACTIVE</b> (using <b>\"" . htmlspecialchars($current_cntrl_ini) . "</b>\")<br>";
    } else {
        // Default control panel INI path
        print "Selective Control Panel INI: <b>INACTIVE</b> (using <b>" . htmlspecialchars("$user_files_dir/controlpanel.ini") . "</b>)<br>";
    }

    // Get system boot time/date
    $upsince = exec("$UPTIME_CMD -s");
    // Get full uptime string including load average
    $loadavg_raw = exec("$UPTIME_CMD");
    // Initialize load average variable
    $loadavg = 'N/A';
    // Try parsing load average from the 'uptime' command output
    if (strpos($loadavg_raw, 'load average:') !== false) {
        $loadavg_parts = explode('load average:', $loadavg_raw);
        $loadavg = trim($loadavg_parts[1]); // Get the part after 'load average:'
    } elseif (file_exists('/proc/loadavg')) { // Fallback: Try reading directly from /proc/loadavg
         $loadavg_parts = explode(' ', file_get_contents('/proc/loadavg'));
         // Format the first three values (1, 5, 15 min averages)
         $loadavg = $loadavg_parts[0] . ', ' . $loadavg_parts[1] . ', ' . $loadavg_parts[2];
    }
    // Display the current date, uptime, and load average
    print "<br>" . htmlspecialchars($myday) . " - Up since: " . htmlspecialchars($upsince) . " - Load Average: " . htmlspecialchars($loadavg) . "<br>";
    print "<br>"; // Add space

    // Define the standard systemd core dump directory
    $core_dir = '/var/lib/systemd/coredump';
    // Initialize core dump count
    $Cores = 0;
    // Check if the directory exists and is readable by the web server
    if (is_dir($core_dir) && is_readable($core_dir)) {
        // Use glob() to safely get a list of files/directories in the core dump dir
        $core_files = glob($core_dir . '/*');
        // Count the number of items found (more reliable than `ls | wc`)
        $Cores = is_array($core_files) ? count($core_files) : 0;
    } else {
        // Suppress errors from ls (2>/dev/null)
        $core_command_output = exec("ls " . escapeshellarg($core_dir) . " 2>/dev/null | wc -w", $core_output_lines, $core_return_var);
        // If the command succeeded (exit code 0) and returned a line, parse the count
         $Cores = ($core_return_var === 0 && isset($core_output_lines[0])) ? intval($core_output_lines[0]) : 0;
    }

    // Display the core dump count, applying CSS classes for warning/error highlighting
    print "[ Core dumps: ";
    if ($Cores >= 1 && $Cores <= 2) { // Warning level
        print "<span class=\"coredump-warning\">" . $Cores . "</span>";
    } elseif ($Cores > 2) { // Error/High level
        print "<span class=\"coredump-error\">" . $Cores . "</span>";
    } else { // Normal (0)
        print "0";
    }
    print " ]<br><br>"; // Close bracket and add space

    // Define temperature thresholds in Celsius for styling
    define('CPU_TEMP_WARNING_THRESHOLD', 50); // Yellow background
    define('CPU_TEMP_HIGH_THRESHOLD', 65);    // Red background

    // Define the path to the external script that fetches CPU temperature
    $temp_script_path = "/usr/local/sbin/supermon/get_temp";
    // Initialize raw temperature variable
    $CPUTemp_raw = '';
    // Check if the temperature script exists and is executable
    if (is_executable($temp_script_path)) {
        // If executable, run the script and get its output
        $CPUTemp_raw = exec($temp_script_path);
    } else {
        // If not executable, set an error message
        $CPUTemp_raw = "Error: Script not executable ($temp_script_path)";
    }

    // 1. Remove any potential HTML tags that might be in the script output
    $cleaned_step1 = strip_tags($CPUTemp_raw);
    // 2. Decode HTML entities (e.g.,   to space, ° to °)
    $cleaned_step2 = html_entity_decode($cleaned_step1, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    // 3. Replace multiple consecutive whitespace characters with a single space
    $cleaned_step3 = preg_replace('/\s+/', ' ', $cleaned_step2);
    // 4. Trim leading/trailing whitespace from the final cleaned string
    $CPUTemp_cleaned = trim($cleaned_step3);

    // Set default CSS class for unknown/unparsed state
    $temp_class = 'cpu-temp-unknown';
    // Prepare default HTML output (shows the cleaned string with the unknown class)
    $output_html = "<span class=\"" . $temp_class . "\">" . htmlspecialchars($CPUTemp_cleaned) . "</span>";

    // Attempt to parse the cleaned string using regex.
    // Expects a format like: "CPU: 105°F, 41°C @ 22:22" (flexible with spacing)
    // Captures: 1=Prefix ("CPU:"), 2=Temp readings ("105°F, 41°C"), 3=Suffix ("@ 22:22")
    if (preg_match('/^(CPU:)\s*(.*?)\s*(@\s*\d{2}:\d{2})$/', $CPUTemp_cleaned, $matches)) {
        // If the main structure matches, extract the parts
        $cpu_prefix_text = trim($matches[1]);
        $temp_text_content = trim($matches[2]); // Contains both F and C temps potentially
        $cpu_suffix_text = trim($matches[3]);

        // Now, try to extract the numerical Celsius value from the $temp_text_content
        $celsius_val = null;
        // Regex to find digits (possibly negative), optional space, optional degree symbol, then 'C'
        if (preg_match('/(-?\d+)\s?°?C/', $temp_text_content, $celsius_matches)) {
            // If Celsius value found, convert it to an integer
            $celsius_val = intval($celsius_matches[1]);

            // Determine the appropriate CSS class based on thresholds
            if ($celsius_val >= CPU_TEMP_HIGH_THRESHOLD) {
                $temp_class = 'cpu-temp-high'; // High temp class (red)
            } elseif ($celsius_val >= CPU_TEMP_WARNING_THRESHOLD) {
                $temp_class = 'cpu-temp-warning'; // Warning temp class (yellow)
            } else {
                $temp_class = 'cpu-temp-normal'; // Normal temp class (green)
            }
        }
        // If Celsius value wasn't found in the text, $temp_class remains 'cpu-temp-unknown'

        // Reconstruct the HTML output using the extracted parts and the determined CSS class
        // Apply htmlspecialchars to prevent XSS from potentially manipulated script output
        $output_html = htmlspecialchars($cpu_prefix_text) .
                       " <span class=\"" . $temp_class . "\">" . // Apply determined class
                       htmlspecialchars($temp_text_content) .     // Display the original temp text block
                       "</span>" .                             // Close the styled span
                       " " . htmlspecialchars($cpu_suffix_text); // Add the suffix

    }

    // Print the final, formatted HTML for the CPU temperature
    print $output_html;
    print "<br><br>"; // Add space

    ?>
    </div> <!-- Close the main info-container div -->
    <center> <!-- Center the button -->
        <!-- Display a "Close Window" button styled using the .submit2 class from supermon.css -->
        <input type="button" class="submit2" Value="Close Window" onclick="self.close();"> <!-- JS closes the popup -->
    </center>
    <br> <!-- Add space after the button -->
</body>
</html>