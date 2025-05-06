<?php
// ami_manager.php: Handles Asterisk Manager Interface interactions

/**
 * Connects and logs into AMI hosts for the specified nodes.
 *
 * @param array $nodes Array of node numbers to connect to.
 * @param array $config The loaded INI configuration.
 * @param callable $sendEventCallback Function to send SSE events (for connection status).
 * @return array {
 *     @var resource[] $fp Array of active AMI socket connections [hostname => socket].
 *     @var array $servers Array indicating successful login status [hostname => 'y'].
 * }
 */
function connect_ami_hosts(array $nodes, array $config, callable $sendEventCallback) {
    $servers = [];
    $fp = [];

    foreach ($nodes as $node) {
        if (!isset($config[$node])) continue;

        $host = $config[$node]['host'];

        // Connect and login to each manager only once per host.
        if (!array_key_exists($host, $servers)) {
            $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Connecting to Asterisk Manager...']);

            $socket = AMIconnect($host);

            if ($socket === FALSE) {
                $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Could not connect to Asterisk Manager.']);
                $fp[$host] = null;
            } else {
                $fp[$host] = $socket;
                // Try to login
                if (AMIlogin($fp[$host], $config[$node]['user'], $config[$node]['passwd'])) {
                    $servers[$host] = 'y';
                } else {
                    $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Could not login to Asterisk Manager.']);
                    fclose($fp[$host]); // Close socket if login failed
                    $fp[$host] = null;
                    unset($servers[$host]); // Ensure it's not marked as connected
                }
            }
        }
    }
    // Filter out failed connections
    $fp = array_filter($fp);
    return ['fp' => $fp, 'servers' => $servers];
}

/**
 * Parses the RptStatus and SawStat responses to build node connection details.
 * This function is kept internal to ami_manager as it directly processes AMI output.
 *
 * @param resource $fp AMI socket resource.
 * @param string $rptStatus Response from RptStatus XStat command.
 * @param string $sawStatus Response from RptStatus SawStat command.
 * @return array Parsed node connection data.
 */
function parseNode($fp, $rptStatus, $sawStatus) {
    $curNodes = array();
    $links = array();
    $conns = array();
    $modes = array(); // Initialize modes
    $longRangeLinks = []; // Initialize longRangeLinks

    // Default values for variables extracted from Var: lines
    $rxKeyed = '0';
    $txKeyed = '0';
    $cputemp = 'N/A';
    $cpuup = 'N/A';
    $cpuload = 'N/A';
    $ALERT = 'N/A';
    $WX = 'N/A';
    $LOGS = 'N/A';


    // Parse 'rptStat Conn:' and 'Var:' lines.
    $lines = explode("\n", $rptStatus);
    foreach ($lines as $line) {
        if (preg_match('/Conn: (.*)/', $line, $matches)) {
            $arr = preg_split("/\s+/", trim($matches[1]));
            // Ensure array has enough elements before accessing them
            if (count($arr) >= 5) {
                 if(is_numeric($arr[0]) && $arr[0] > 3000000) {
                     // Echolink node - format might differ slightly, adjust indices if needed
                     $conns[] = array($arr[0], "", $arr[1], $arr[2], $arr[3], $arr[4]); // Assuming format matches
                 } else {
                     // Standard Allstar node
                     $conns[] = $arr;
                 }
            }
        } elseif (preg_match('/Var: RPT_RXKEYED=(.*)/', $line, $matches)) {
           $rxKeyed = trim($matches[1]);
        } elseif (preg_match('/Var: RPT_TXKEYED=(.*)/', $line, $matches)) {
           $txKeyed = trim($matches[1]);
        } elseif (preg_match('/Var: cpu_temp=(.*)/', $line, $matches)) {
           $cputemp = trim($matches[1]);
        } elseif (preg_match('/Var: cpu_up=(.*)/', $line, $matches)) {
           $cpuup = trim($matches[1]);
        } elseif (preg_match('/Var: cpu_load=(.*)/', $line, $matches)) {
           $cpuload = trim($matches[1]);
        } elseif (preg_match('/Var: ALERT=(.*)/', $line, $matches)) {
           $ALERT = trim($matches[1]);
        } elseif (preg_match('/Var: WX=(.*)/', $line, $matches)) {
           $WX = trim($matches[1]);
        } elseif (preg_match('/Var: LOGS=(.*)/', $line, $matches)) {
           $LOGS = trim($matches[1]);
        } elseif (preg_match("/LinkedNodes: (.*)/", $line, $matches)) {
             if (!empty(trim($matches[1]))) {
                 $longRangeLinks = preg_split("/, /", trim($matches[1]));
             }
        }
    }


    // Process LinkedNodes after parsing all lines
    foreach ($longRangeLinks as $linkLine) {
        if (!empty($linkLine)) {
            $n = substr($linkLine, 1);
            $modes[$n]['mode'] = substr($linkLine, 0, 1);
        }
    }


    // Parse 'sawStat Conn:' lines.
    $keyups = array();
    $lines = explode("\n", $sawStatus);
    foreach ($lines as $line) {
        if (preg_match('/Conn: (.*)/', $line, $matches)) {
            $arr = preg_split("/\s+/", trim($matches[1]));
            if (count($arr) >= 4 && isset($arr[0])) { // Basic validation
                 $keyups[$arr[0]] = array('node' => $arr[0], 'isKeyed' => $arr[1], 'keyed' => $arr[2], 'unkeyed' => $arr[3]);
            }
        }
    }

    // Pull above arrays together into $curNodes
    if (count($conns) > 0) {
        foreach ($conns as $nodeData) {
            if (!isset($nodeData[0])) continue;
            $n = $nodeData[0];

            $curNodes[$n]['node'] = $n;
            $curNodes[$n]['info'] = getAstInfo($fp, $n);
            $curNodes[$n]['ip'] = $nodeData[1] ?? 'N/A';
            $curNodes[$n]['direction'] = $nodeData[3] ?? 'N/A';
            $curNodes[$n]['elapsed'] = $nodeData[4] ?? 'N/A';
            $curNodes[$n]['link'] = $nodeData[5] ?? null;

            // Fix for table display bug of IRLP nodes / Echolink connections without explicit link state
             if ($curNodes[$n]['link'] === null && ($n > 3000000 || isset($nodeData[5]))) {
                 $curNodes[$n]['direction'] = $nodeData[2] ?? 'N/A';
                 $curNodes[$n]['elapsed'] = $nodeData[3] ?? 'N/A';
                 if (isset($modes[$n]['mode'])) {
                     $curNodes[$n]['link'] = ($modes[$n]['mode'] == 'C') ? "CONNECTING" : "ESTABLISHED";
                 } else {
                     // If mode not found for these types, default link state?
                     $curNodes[$n]['link'] = "ESTABLISHED";
                 }
            } elseif ($curNodes[$n]['link'] === null) {
                 if (isset($modes[$n]['mode'])) {
                     $curNodes[$n]['link'] = ($modes[$n]['mode'] == 'C') ? "CONNECTING" : "ESTABLISHED";
                 } else {
                      $curNodes[$n]['link'] = "ESTABLISHED";
                 }
            }


            // Get mode if available
            if (isset($modes[$n])) {
                $curNodes[$n]['mode'] = $modes[$n]['mode'];
            } else {
                $curNodes[$n]['mode'] = 'Local RX';
            }


            // Add Keyed status from $keyups
            if (isset($keyups[$n])) {
                 $curNodes[$n]['keyed'] = ($keyups[$n]['isKeyed'] == 1) ? 'yes' : 'no';
                 $curNodes[$n]['last_keyed'] = $keyups[$n]['keyed'];
            } else {
                 $curNodes[$n]['keyed'] = 'n/a';
                 $curNodes[$n]['last_keyed'] = '-1';
            }

            // Add extra vars (these apply to the main node, assign them here)
             $curNodes[$n]['cpu_temp'] = $cputemp;
             $curNodes[$n]['cpu_up'] = $cpuup;
             $curNodes[$n]['cpu_load'] = $cpuload;
             $curNodes[$n]['ALERT'] = $ALERT;
             $curNodes[$n]['WX'] = $WX;
             $curNodes[$n]['LOGS'] = $LOGS;
        }
    }

     // Add placeholder 'node 1' structure if no connections exist or to hold local status
     if (!isset($curNodes[1])) {
         $curNodes[1] = array();
         $curNodes[1]['info'] = (count($conns) == 0) ? "NO CONNECTION" : "Local Status";
     }
     // Always assign local status indicators to node '1' entry
     $curNodes[1]['node'] = 1;
     $curNodes[1]['cos_keyed'] = ($rxKeyed === '1') ? 1 : 0;
     $curNodes[1]['tx_keyed'] = ($txKeyed === '1') ? 1 : 0;
     $curNodes[1]['cpu_temp'] = $cputemp;
     $curNodes[1]['cpu_up'] = $cpuup;
     $curNodes[1]['cpu_load'] = $cpuload;
     $curNodes[1]['ALERT'] = $ALERT;
     $curNodes[1]['WX'] = $WX;
     $curNodes[1]['LOGS'] = $LOGS;


    // If after all processing, node '1' still lacks essential keys from loop, add N/A
    $defaultKeys = ['ip' => 'N/A', 'direction' => 'N/A', 'elapsed' => 'N/A', 'link' => 'N/A', 'mode' => 'Local RX', 'keyed' => 'n/a', 'last_keyed' => '-1'];
    foreach ($defaultKeys as $key => $value) {
        if (!isset($curNodes[1][$key])) {
             $curNodes[1][$key] = $value;
        }
    }


    return $curNodes;

}


/**
 * Gets status for a specific node using AMI commands.
 *
 * @param resource $fp The AMI socket connection.
 * @param string|int $node The node number.
 * @param callable $sendEventCallback Function to send SSE events for errors.
 * @return array The parsed node status data from parseNode().
 */
function getNode($fp, $node, callable $sendEventCallback) {
    $actionRand = mt_rand();
    $rptStatus = '';
    $sawStatus = '';

    // Get RptStatus XStat
    $actionID_xstat = 'xstat' . $actionRand;
    if (fwrite($fp, "ACTION: RptStatus\r\nCOMMAND: XStat\r\nNODE: $node\r\nActionID: $actionID_xstat\r\n\r\n")) {
        $rptStatus = AMIget_response($fp, $actionID_xstat);
    } else {
        $sendEventCallback('error', ['node' => $node, 'status' => 'XStat AMI write failed!']);
        return [];
    }

    // Get RptStatus SawStat
    $actionID_sawstat = 'sawstat' . $actionRand;
    if (fwrite($fp, "ACTION: RptStatus\r\nCOMMAND: SawStat\r\nNODE: $node\r\nActionID: $actionID_sawstat\r\n\r\n")) {
        $sawStatus = AMIget_response($fp, $actionID_sawstat);
    } else {
        $sendEventCallback('error', ['node' => $node, 'status' => 'SawStat AMI write failed!']);
        return [];
    }

    // Pass $fp needed for getAstInfo inside parseNode
    return parseNode($fp, $rptStatus, $sawStatus);
}

/**
 * Disconnects from all AMI hosts, attempting a graceful logoff first.
 *
 * @param resource[] $fp Array of AMI socket connections [host => socket].
 */
function disconnect_ami_hosts(array $fp) {
    foreach ($fp as $host => $socket) {
        // Check if $socket is a valid resource before proceeding
        if ($socket && is_resource($socket)) {
            // Attempt graceful logoff using AMIlogoff function
            AMIlogoff($socket);

            // Close the socket regardless of logoff success
            fclose($socket);
        }
    }
}

?>
