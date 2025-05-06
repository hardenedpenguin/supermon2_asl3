<?php

// No debug error reporting here

include("session.inc");
include("amifunctions.inc"); // Ensure this points to the FINAL corrected version
include("common.inc");
include("authusers.php");
include("authini.php");

// Author: Paul Aidukas KN2R (Copyright) July 15, 2013
// For ham radio use only, NOT for comercial use!
// Be sure to allow popups from your Allmon web server to your browser!!
// Major update by KB4FXC 02/2018
// Minor updates by KN2R 04/2019
// AMIcommand interaction updated based on discussion July 2024

// Define highlight color for better readability on black background
define('HIGHLIGHT_COLOR', 'aqua'); // or 'lightblue', 'dodgerblue', '#66ccff'

?>
<html>
<head>
    <title>AllStar Status</title>
    <style>
        /* Set the entire page background to black */
        body {
            background-color: black;
            color: white; /* Set default text color to white for elements outside pre */
            margin: 0; /* Remove default body margin */
            padding: 10px; /* Add some padding around the content */
        }

        /* Style for the main pre block */
        .status-output {
            font-family: "Courier New", Courier, monospace; /* Ensure monospaced font */
            font-size: 16px;
            background-color: black; /* Keep pre background black (matches body) */
            color: white; /* Keep pre text white */
            padding: 15px; /* Add some padding inside pre */
            border-radius: 5px; /* Optional: slightly rounded corners */
            line-height: 1.3; /* Improve line spacing */
            margin: 0; /* Remove default margin */
        }

        /* Define color in CSS for easier changes */
        .highlight {
            color: <?php echo HIGHLIGHT_COLOR; ?>;
            font-weight: bold;
        }
        .header-line {
            color: <?php echo HIGHLIGHT_COLOR; ?>;
        }
        .section-title {
            color: yellow;
        }
        .error-msg {
            color: red;
            font-weight: bold;
        }
        .node-id {
            color: limegreen;
            font-weight: bold;
        }
        .count {
            color: orange;
            font-weight: bold;
        }
        .none-indicator {
            color: gray;
            font-style: italic;
        }
        /* Style for the error H3 tag when not logged in */
        h3.error-msg {
             padding: 15px;
        }
    </style>
</head>
<body>
<!-- Apply background/text color and font size via class -->
<pre class="status-output">
<?php
    // Check if user is logged in and has the required authorization
    if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && function_exists('get_user_auth') && get_user_auth("ASTATUSER")) {

        // WA3DSP 4/2021 Added check for ini file based on username
        $SUPINI = get_ini_name($_SESSION['user']);

        // Read supermon INI file
        if (!file_exists($SUPINI)) {
            die("<span class='error-msg'>ERROR:</span> Couldn't load <span class='highlight'>$SUPINI</span> ini file.\n");
        }

        $config = parse_ini_file($SUPINI, true);

        // Get node parameters from GET request, sanitize them
        $node = isset($_GET['node']) ? trim(strip_tags($_GET['node'])) : null;
        // $localnode = isset($_GET['localnode']) ? trim(strip_tags($_GET['localnode'])) : null; // Not used

        if (empty($node)) {
             die("<span class='error-msg'>ERROR:</span> 'node' parameter is missing in the URL.");
        }
        if (!isset($config[$node])) {
             die("<span class='error-msg'>ERROR:</span> Node <span class='node-id'>$node</span> is not in <span class='highlight'>$SUPINI</span> file.");
        }

        // Set up Asterisk manager connection
        $fp = AMIconnect($config[$node]['host']); // Add timeout if desired: AMIconnect($host, $timeout)
        if ($fp === false) {
            die("<span class='error-msg'>ERROR:</span> Could not connect to Asterisk Manager on host <span class='highlight'>{$config[$node]['host']}</span>.");
        }

        // Login to Asterisk manager
        $loginSuccess = AMIlogin($fp, $config[$node]['user'], $config[$node]['passwd']); // Add timeout if desired
        if ($loginSuccess === false) {
            @fclose($fp);
            die("<span class='error-msg'>ERROR:</span> Could not login to Asterisk Manager using user <span class='highlight'>{$config[$node]['user']}</span>. Check credentials and manager.conf permissions.");
        }

        // Display status information
        page_header();
        show_all_nodes($fp);
        show_peers($fp);
        show_channels($fp);
        show_netstats($fp);

        // Logout from Asterisk Manager
        AMIlogoff($fp); // Use the logoff function

    } else {
        echo ("<h3 class='error-msg'>ERROR: You Must login and have ASTATUSER permission to use this function!</h3>");
    }
?>
</pre>
</body>
</html>

<?php       // Local Functions...

/**
 * Displays the page header with hostname and date.
 */
function page_header()
{
    global $HOSTNAME, $AWK, $DATE; // These globals come from common.inc presumably

    // Define full paths if needed, otherwise assume they are in PATH
    $HOSTNAME_CMD = isset($HOSTNAME) ? $HOSTNAME : 'hostname';
    $AWK_CMD = isset($AWK) ? $AWK : 'awk';
    $DATE_CMD = isset($DATE) ? $DATE : 'date';

    echo "<span class='header-line'>#################################################################</span>\n";
    // Add error suppression or checking for backtick commands if desired
    $host = trim(`$HOSTNAME_CMD | $AWK_CMD -F. '{printf ("%s", $1);}' 2>/dev/null`);
    $date = trim(`$DATE_CMD 2>/dev/null`);
    echo " <span class='highlight'>" . htmlspecialchars($host) . "</span> AllStar Status: <span class='highlight'>" . htmlspecialchars($date) . "</span>\n";
    echo "<span class='header-line'>#################################################################</span>\n";
    echo "\n";
}

/**
 * Displays connection and status information for local nodes.
 * (Uses corrected AMIcommand from amifunctions.inc that handles 'Output: ' lines)
 *
 * @param resource $fp Active AMI connection resource.
 */
function show_all_nodes($fp)
{
    global $TAIL, $HEAD, $GREP, $SED; // These globals come from common.inc presumably

    // Define full paths if needed, otherwise assume they are in PATH
    $TAIL_CMD = isset($TAIL) ? $TAIL : '/usr/bin/tail';
    $HEAD_CMD = isset($HEAD) ? $HEAD : '/usr/bin/head';
    $GREP_CMD = isset($GREP) ? $GREP : '/bin/grep'; // Or /bin/egrep if needed later
    $SED_CMD = isset($SED) ? $SED : '/bin/sed';
    $ECHO_CMD = '/bin/echo'; // Using full path for echo is generally safer

    // 1. Get the CORRECT multi-line output from the FIXED AMIcommand function
    $nodes_output = AMIcommand($fp, "rpt localnodes"); // Add timeout parameter if needed

    // Handle case where AMIcommand might return false or empty string
    if ($nodes_output === false) {
        echo "<span class='error-msg'>Error:</span> Failed to execute 'rpt localnodes' command.\n";
        return; // Exit the function
    }
    if (trim($nodes_output) === '') {
         echo "<span class='none-indicator'>No local nodes reported by Asterisk.</span>\n";
         return; // Exit the function
    }

    // 2. Split the multi-line string into an array of lines
    // The output from AMIcommand should now contain the lines *without* "Output: " prefix
    // Example: "\nNode\n----\n546051\n546055\n546056\n"
    $nodelist = explode("\n", $nodes_output);
    $node_count = count($nodelist);

    // 3. Loop through the array, skipping potential empty lines or headers, and validating nodes
    $processed_node_count = 0;
    // Adjust loop start based on expected output format from AMIcommand (which removed "Output: ")
    // Start from 0 and check each line
    for ($i = 0; $i < $node_count; $i++) {
        $node_num_raw = trim($nodelist[$i]);

        // Skip empty lines or lines that don't look like our target content (Node, ----, or digits)
        // This makes it slightly more robust if the header format changes subtly
        if (empty($node_num_raw) || $node_num_raw === "Node" || $node_num_raw === "----") {
             continue;
        }

        // Validate if it's digits (actual node number)
        if (!ctype_digit($node_num_raw)) {
            // Log unexpected line if desired, but continue
            continue;
        }

        $processed_node_count++;
        $node_num = $node_num_raw; // Valid node number found

        // Retrieve extended node information (xnode)
        $AMI1 = AMIcommand($fp, "rpt xnode $node_num");
        if ($AMI1 === false) {
             echo "Node <span class='node-id'>$node_num</span>: <span class='error-msg'>Error retrieving xnode info.</span>\n\n";
             continue;
         }
         if (trim($AMI1) === '') {
             echo "Node <span class='node-id'>$node_num</span>: <span class='none-indicator'>No xnode info returned.</span>\n\n";
             continue;
         }

        // Extract and display node connections (Using shell commands)
        $cmd_cnodes3 = "$ECHO_CMD -n " . escapeshellarg($AMI1) . " | $GREP_CMD \"^RPT_ALINKS\" | $SED_CMD 's/,/: /' | $SED_CMD 's/[a-zA-Z\=\_]//g'";
        $CNODES3 = trim(`$cmd_cnodes3 2>&1`);
        echo "Node <span class='node-id'>$node_num</span> connections => <span class='highlight'>" . htmlspecialchars($CNODES3) . "</span>\n";

        echo "\n<span class='section-title'>************************* CONNECTED NODES *************************</span>\n";

        // Extract connected nodes list from xnode output (Using shell commands)
        $cmd_n3 = "$ECHO_CMD -n " . escapeshellarg($AMI1) . " | $TAIL_CMD --lines=+3 | $HEAD_CMD --lines=1";
        $N3 = trim(`$cmd_n3 2>&1`);
        $res = explode(", ", $N3);
        $CNODES2 = count($res);
        $tmp = isset($res[0]) ? trim($res[0]) : '';

        if ("$tmp" != "<NONE>" && !empty($tmp) && $CNODES2 > 0) {
            printf(" <span class='count'>%3s</span> node(s) total:\n     ", $CNODES2);
            $k = 0;
            for ($j = 0; $j < $CNODES2; $j++) {
                 printf("<span class='node-id'>%8s</span>", htmlspecialchars(trim($res[$j])));
                 if ($j < $CNODES2 - 1) { echo ", "; }
                 $k++;
                 if ($k >= 10 && $j < $CNODES2 - 1) { $k = 0; echo "\n     "; }
            }
            echo "\n\n";
        } else {
             echo "<span class='none-indicator'>" . htmlspecialchars("<NONE>") . "</span>\n\n";
        }

        echo "<span class='section-title'>***************************** LSTATS ******************************</span>\n";

        // Retrieve local node statistics (lstats)
        $AMI2 = AMIcommand($fp, "rpt lstats $node_num");
         if ($AMI2 === false) {
             echo "<span class='error-msg'>Error retrieving lstats info for node $node_num.</span>\n\n\n";
             continue;
         }
         if (trim($AMI2) === '') {
              echo "<span class='none-indicator'>No lstats info returned for node $node_num.</span>\n\n\n";
             continue;
         }

        // Display lstats output (Using shell commands)
        $cmd_lstats = "$ECHO_CMD -n " . escapeshellarg($AMI2) . " | $HEAD_CMD --lines=-1";
        $N = trim(`$cmd_lstats 2>&1`);
        echo htmlspecialchars($N) . "\n\n\n";

   } // End FOR loop iterating through nodes

   // Check if any nodes were actually processed
   if ($processed_node_count == 0 && trim($nodes_output) !== '') {
        // We got output, but didn't find any lines that were just digits after filtering headers/blanks
        echo "<span class='error-msg'>Warning:</span> Node list retrieved, but no valid node numbers identified in the output:\n<pre>" . htmlspecialchars($nodes_output) . "</pre>\n";
   }
}


/**
 * Displays active IAX2 channel information.
 *
 * @param resource $fp Active AMI connection resource.
 */
function show_channels($fp)
{
    global $HEAD;
    $HEAD_CMD = isset($HEAD) ? $HEAD : '/usr/bin/head';
    $ECHO_CMD = '/bin/echo';

    $AMI1 = AMIcommand($fp, "iax2 show channels");

    echo "<span class='section-title'>**************************** CHANNELS *****************************</span>\n";

    if ($AMI1 === false) { echo "<span class='error-msg'>Error retrieving IAX2 channel info.</span>\n\n"; return; }
    if (trim($AMI1) === '') { echo "<span class='none-indicator'>No IAX2 channels reported.</span>\n\n"; return; }

    $cmd_channels = "$ECHO_CMD -n ". escapeshellarg($AMI1) ." | $HEAD_CMD --lines=-1";
    $channels = trim(`$cmd_channels 2>&1`);
    echo htmlspecialchars($channels) . "\n\n";
}

/**
 * Displays IAX2 network statistics.
 *
 * @param resource $fp Active AMI connection resource.
 */
function show_netstats($fp)
{
    global $HEAD;
    $HEAD_CMD = isset($HEAD) ? $HEAD : '/usr/bin/head';
    $ECHO_CMD = '/bin/echo';

    $AMI1 = AMIcommand($fp, "iax2 show netstats");

    echo "<span class='section-title'>**************************** NETSTATS *****************************</span>\n";

     if ($AMI1 === false) { echo "<span class='error-msg'>Error retrieving IAX2 netstats info.</span>\n\n"; return; }
     if (trim($AMI1) === '') { echo "<span class='none-indicator'>No IAX2 netstats reported.</span>\n\n"; return; }

    $cmd_netstats = "$ECHO_CMD -n ". escapeshellarg($AMI1) ." | $HEAD_CMD --lines=-1";
    $netstats = trim(`$cmd_netstats 2>&1`);
    echo htmlspecialchars($netstats) . "\n\n";
}

/**
 * Displays IAX2 peer information, excluding self and unspecified.
 *
 * @param resource $fp Active AMI connection resource.
 */
function show_peers($fp)
{
    global $HEAD, $EGREP;
    $HEAD_CMD = isset($HEAD) ? $HEAD : '/usr/bin/head';
    $EGREP_CMD = isset($EGREP) ? $EGREP : '/bin/egrep'; // Or /bin/grep -E
    $ECHO_CMD = '/bin/echo';

    $AMI1 = AMIcommand($fp, "iax2 show peers");

    echo "<span class='section-title'>*************************** OTHER PEERS ***************************</span>\n";

    if ($AMI1 === false) { echo "<span class='error-msg'>Error retrieving IAX2 peer info.</span>\n\n\n"; return; }
    if (trim($AMI1) === '') { echo "<span class='none-indicator'>No IAX2 peers reported.</span>\n\n\n"; return; }

    $cmd_peers = "$ECHO_CMD -n ". escapeshellarg($AMI1) ." | $HEAD_CMD --lines=-1 | $EGREP_CMD -v '^Name|iax2 peers|Unspecified|^$'";
    $peers = trim(`$cmd_peers 2>&1`);

    if (!empty($peers)) {
        echo htmlspecialchars($peers) . "\n\n\n";
    } else {
        echo "<span class='none-indicator'>" . htmlspecialchars("<NONE>") . "</span>\n\n\n"; // Indicate filtering resulted in no peers shown
    }
}

?>
