<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

// Set timezone
date_default_timezone_set('America/Chicago');

include('amifunctions.inc');
include('nodeinfo.inc');
include("user_files/global.inc");
include("common.inc");
include("authini.php");

// --- Input Validation ---
if (empty($_GET['node'])) {
    // Send an error message via SSE format
    echo "id: error\n";
    echo "event: error\n";
    echo "data: Unknown voter request! Missing 'node' parameter.\n\n";
    ob_flush();
    flush();
    exit;
}

// Read and sanitize the node parameter
$node = trim(strip_tags($_GET['node']));

// --- Load Allstar Database ---
$db_path = $ASTDB_TXT; // Defined in common.inc
$astdb = array();
if (file_exists($db_path)) {
    $fh = fopen($db_path, "r");
    if ($fh && flock($fh, LOCK_SH)) { // Ensure file opened successfully before locking
        while (($line = fgets($fh)) !== FALSE) {
            // Use explode with limit 2 in case description contains '|'
            $arr = explode("|", trim($line), 2);
            if (count($arr) >= 1) { // Ensure there's at least a node number
                $astdb[$arr[0]] = $arr;
            }
        }
        flock($fh, LOCK_UN);
        fclose($fh);
    } else {
        // Handle error opening or locking file if necessary
        error_log("Could not open or lock $db_path");
    }
}

// Determine INI file based on logged-in user (ensure session is started if needed)
if (!isset($_SESSION['user'])) {
    // Handle not logged in state appropriate for SSE
    echo "id: error\n";
    echo "event: error\n";
    echo "data: User not logged in or session expired.\n\n";
    ob_flush();
    flush();
    exit;
}
$SUPINI = get_ini_name($_SESSION['user']);

// Read config INI file
if (!file_exists($SUPINI)) {
    echo "id: error\n";
    echo "event: error\n";
    // Be careful not to expose full path in production errors
    echo "data: Couldn't load configuration file.\n\n";
    ob_flush();
    flush();
    exit; // Use exit instead of die for cleaner termination
}
$config = parse_ini_file($SUPINI, true);

// Check if config for the specific node exists
if (!isset($config[$node])) {
    echo "id: error\n";
    echo "event: error\n";
    echo "data: Configuration for node '$node' not found in $SUPINI.\n\n";
    ob_flush();
    flush();
    exit;
}

// --- Connect to Asterisk Manager Interface (AMI) ---
echo "data: Connecting to AMI...\n\n"; // Initial status message
ob_flush();
flush();

$fp = AMIconnect($config[$node]['host']);
if (FALSE === $fp) {
    echo "id: error\n";
    echo "event: error\n";
    echo "data: Could not connect to AMI on host {$config[$node]['host']}.\n\n";
    ob_flush();
    flush();
    exit;
}

if (FALSE === AMIlogin($fp, $config[$node]['user'], $config[$node]['passwd'])) {
    echo "id: error\n";
    echo "event: error\n";
    echo "data: Could not login to AMI.\n\n";
    ob_flush();
    flush();
    fclose($fp); // Close the connection on login failure
    exit;
}

// --- Main Loop ---
$ticTocChars = ['|', '/', '-', '\\'];
$ticTocIndex = 0;
$actionIDBase = "voter{$node}"; // Base ActionID

while (TRUE) {
    // Generate unique ActionID for each request
    $actionID = $actionIDBase . mt_rand(1000, 9999);

    // Get voter status response from AMI
    $response = get_voter($fp, $actionID);

    // Check connection and response validity
    if ($response === FALSE) {
        // Attempt to reconnect or handle error gracefully
        echo "id: error\n";
        echo "event: error\n";
        echo "data: Lost connection or bad response from AMI. Attempting reconnect...\n\n";
        ob_flush();
        flush();
        fclose($fp);
        sleep(2); // Wait before reconnecting

        // Reconnect logic (similar to initial connection)
        $fp = AMIconnect($config[$node]['host']);
        if (FALSE === $fp || FALSE === AMIlogin($fp, $config[$node]['user'], $config[$node]['passwd'])) {
            echo "id: error\n";
            echo "event: error\n";
            echo "data: Reconnect failed. Exiting.\n\n";
            ob_flush();
            flush();
            if ($fp) fclose($fp);
            exit; // Exit if reconnect fails
        }
        echo "data: Reconnected to AMI.\n\n";
        ob_flush();
        flush();
        continue;
    }

    // --- Process AMI Response ---
    $lines = preg_split("/\r?\n/", $response);
    $voted = array();
    $nodesData = array(); // Use a more descriptive name
    $currentNode = null; // Track the current node being processed

    foreach ($lines as $line) {
        $line = trim($line);
        if (strlen($line) == 0 || strpos($line, ': ') === false) {
            continue; // Skip empty lines or lines without a colon-space separator
        }

        list($key, $value) = explode(": ", $line, 2); // Limit split to 2 parts
        $key = trim($key); // Trim whitespace from key
        $value = trim($value); // Trim whitespace from value

        // Use switch for cleaner key handling
        switch ($key) {
            case 'Node':
                $currentNode = $value;
                if (!isset($nodesData[$currentNode])) {
                    $nodesData[$currentNode] = array(); // Initialize node array if not set
                }
                break;
            case 'Client':
                if ($currentNode !== null) {
                    // Store Client temporarily, wait for RSSI/IP
                    $currentClient = $value;
                    if (!isset($nodesData[$currentNode][$currentClient])) {
                         $nodesData[$currentNode][$currentClient] = array(); // Initialize client array
                    }
                }
                break;
            case 'RSSI':
                if ($currentNode !== null && isset($currentClient)) {
                    $nodesData[$currentNode][$currentClient]['rssi'] = $value;
                }
                break;
            case 'IP':
                 if ($currentNode !== null && isset($currentClient)) {
                    $nodesData[$currentNode][$currentClient]['ip'] = $value;
                 }
                 // Reset currentClient after getting IP (assuming RSSI/IP always come after Client)
                 unset($currentClient);
                break;
            case 'Voted':
                if ($currentNode !== null) {
                    $voted[$currentNode] = $value;
                }
                break;
        }
    }

    // Only print the table for the requested node
    if (isset($nodesData[$node])) {
        $message = printNode($node, $nodesData, $voted, $config[$node]);
    } else {
        // Handle case where the requested node didn't report data in this cycle
        $message = "<p>No data received for node $node in this cycle.</p>";
    }


    // Update spinner
    $ticToc = $ticTocChars[$ticTocIndex];
    $ticTocIndex = ($ticTocIndex + 1) % count($ticTocChars);

    // Send the main data (HTML table)
    echo "id: " . time() . "-data\n"; // Unique ID for the data event
    echo "event: update\n";           // Custom event name
    echo "data: " . str_replace("\n", "\ndata: ", $message) . "\n"; // Properly format multi-line data

    // Send the spinner status
    echo "id: " . time() . "-tick\n"; // Unique ID for the tick event
    echo "event: tick\n";            // Custom event name for spinner
    echo "data: " . $ticToc . "\n\n"; // Send spinner character and finish the event block

    // Flush output buffers
    ob_flush();
    flush();

    // Pause before next iteration
    usleep(150000); // 150ms
}

// Close AMI connection if the loop somehow exits (though it's infinite)
if (isset($fp) && is_resource($fp)) {
    AMIlogoff($fp); // Use logoff function if available, otherwise just close
    fclose($fp);
}
exit;

// ====================
// Function Definitions
// ====================

/**
 * Generates HTML table for a specific node's voter status.
 *
 * @param string $nodeNum The node number to display.
 * @param array $nodesData The processed data from AMI (structure: [$nodeNum => [$clientName => ['rssi'=>val, 'ip'=>val]]]).
 * @param array $votedData Array indicating which client is voted for each node (structure: [$nodeNum => $clientName]).
 * @param array $nodeConfig Configuration specific to this node from the INI file.
 * @return string HTML representation of the node's status.
 */
function printNode($nodeNum, $nodesData, $votedData, $nodeConfig)
{
    global $fp; // Needed for getAstInfo

    // Fetch additional node info (like description)
    // Ensure getAstInfo handles potential errors gracefully
    $info = getAstInfo($fp, $nodeNum);
    $message = '';

    // Start Table
    $message .= "<table class='rtcm'>"; // Use CSS class for styling

    // Table Header
    $headerText = "Node $nodeNum";
    if (!empty($info)) {
        $headerText .= " - $info";
    }
    if (@$nodeConfig['hideNodeURL'] != 1) {
        $nodeURL = htmlspecialchars("http://stats.allstarlink.org/nodeinfo.cgi?node=" . urlencode($nodeNum));
        $message .= "<tr><th colspan='2'><i>   <a href=\"$nodeURL\" target=\"_blank\" rel=\"noopener noreferrer\">" . htmlspecialchars($headerText) . "</a>   </i></th></tr>";
    } else {
        $message .= "<tr><th colspan='2'><i>   " . htmlspecialchars($headerText) . "   </i></th></tr>";
    }
    $message .= "<tr><th>Client</th><th>RSSI</th></tr>";

    // Table Body
    if (!isset($nodesData[$nodeNum]) || empty($nodesData[$nodeNum])) {
        $message .= "<tr><td colspan='2' style='text-align: center; font-style: italic;'>No clients connected</td></tr>";
    } else {
        $clients = $nodesData[$nodeNum];
        $votedClient = isset($votedData[$nodeNum]) ? $votedData[$nodeNum] : 'none'; // Get voted client for this node

        foreach ($clients as $clientName => $clientData) {
            $rssi = isset($clientData['rssi']) ? intval($clientData['rssi']) : 0; // Ensure RSSI is an integer
            $percent = ($rssi > 0 && $rssi <= 255) ? ($rssi / 255.0) * 100 : 0; // Calculate percentage (0-100)
            if ($percent < 1 && $rssi > 0) { // Ensure at least 1% width if RSSI > 0
                 $percent = 1;
            }
            $barWidth = $percent * 3; // Scale percentage for bar width (adjust multiplier as needed)

            // Determine bar color and text color
            $barcolor = "#0099FF"; // Default blue
            $textcolor = 'white';

            if ($votedClient !== 'none' && strpos($clientName, $votedClient) !== false) {
                // Using strpos as strstr is case-sensitive and might not be ideal if case varies
                $barcolor = 'greenyellow';
                $textcolor = 'black';
            } elseif (stripos($clientName, 'Mix') !== false) { // Case-insensitive check for 'Mix'
                $barcolor = 'cyan';
                $textcolor = 'black';
            }

            // Sanitize output
            $safeClientName = htmlspecialchars($clientName);
            $safeRssi = htmlspecialchars($rssi);

            // Print table row for the client
            $message .= "<tr>";
            $message .= "<td><div class='client-name'>" . $safeClientName . "</div></td>"; // Add class for potential styling
            $message .= "<td>";
            $message .= "<div class='rssi-bar-container'>"; // Container for bar + text
            $message .= "<div class='bar' style='width: " . $barWidth . "px; background-color: $barcolor; color: $textcolor;'>";
            // Display RSSI inside the bar only if there's enough space, otherwise maybe next to it?
            // Simple approach: always display inside for now.
            $message .= $safeRssi;
            $message .= "</div>"; // End bar
            $message .= "</div>"; // End rssi-bar-container
            $message .= "</td>";
            $message .= "</tr>";
        }
    }

    // Table Footer
    $message .= "</table><br/>";

    return $message;
}

/**
 * Sends VoterStatus command and retrieves the response from AMI.
 *
 * @param resource $fp The active AMI socket connection.
 * @param string $actionID A unique ActionID for this command.
 * @return string|false The raw response string from AMI, or FALSE on failure.
 */
function get_voter($fp, $actionID)
{
    // Check if connection is still valid before writing
    if (!is_resource($fp) || feof($fp)) {
        return false;
    }

    $command = "ACTION: VoterStatus\r\nActionID: $actionID\r\n\r\n";

    if (@fwrite($fp, $command) > 0) {
        // Get Voter Status response using the provided function
        // Ensure AMIget_response handles timeouts and finds the correct response based on ActionID
        return AMIget_response($fp, $actionID);
    } else {
        // Write failed, likely connection issue
        return FALSE;
    }
}

?>