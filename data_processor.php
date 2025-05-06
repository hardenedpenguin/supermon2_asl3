<?php
// data_processor.php: Functions for processing retrieved node data

/**
 * Sorts connected nodes based on last keyed time.
 * Nodes never heard are placed last, sorted numerically by node number.
 *
 * @param array $nodes Associative array of node data [nodeNum => dataArray].
 *                     Each dataArray must contain 'last_keyed' (in seconds or -1).
 * @return array Nodes sorted by last_keyed time (ascending), then never heard nodes.
 */
function sortNodes(array $nodes) {
    $heard = [];
    $never_heard = [];

    // Separate heard and unheard nodes
    foreach ($nodes as $nodeNum => $row) {
        // Ensure last_keyed exists, default to -1 if not
        $last_keyed_seconds = isset($row['last_keyed']) ? (int)$row['last_keyed'] : -1;

        if ($last_keyed_seconds == -1) {
            $never_heard[$nodeNum] = $row; // Keep full row data
        } else {
            $heard[$nodeNum] = $last_keyed_seconds; // Store only the time for sorting
        }
    }

    // Sort nodes that have been heard by time (ascending)
    asort($heard, SORT_NUMERIC);

    // Sort nodes that have never been heard by node number (ascending)
    ksort($never_heard, SORT_NUMERIC);

    // Rebuild the sorted node array
    $sortedNodes = [];
    foreach ($heard as $nodeNum => $time) {
        // Format last_keyed time for display
        $t = $nodes[$nodeNum]['last_keyed']; // Get original seconds value
        $h = floor($t / 3600);
        $m = floor(($t / 60) % 60);
        $s = $t % 60;
        $nodes[$nodeNum]['last_keyed_formatted'] = sprintf("%03d:%02d:%02d", $h, $m, $s);
        $sortedNodes[$nodeNum] = $nodes[$nodeNum];
    }

    foreach ($never_heard as $nodeNum => $row) {
        $nodes[$nodeNum]['last_keyed_formatted'] = "Never"; // Set formatted string
        $sortedNodes[$nodeNum] = $nodes[$nodeNum];
    }

    return $sortedNodes;
}

/**
 * Prepares the final data structure for SSE events, separating time-sensitive data.
 *
 * @param array $nodes_in Node number array.
 * @param array $config Loaded INI config.
 * @param resource[] $ami_connections Array of AMI connections [hostname => socket].
 * @param array $ami_servers Array of logged-in server statuses [hostname => 'y'].
 * @param callable $sendEventCallback Callback to send SSE events.
 * @return array {
 *     @var array $current_data Node data excluding volatile time fields.
 *     @var array $time_data Node data containing only volatile time fields.
 * }
 */
function prepare_sse_data(array $nodes_in, array $config, array $ami_connections, array $ami_servers, callable $sendEventCallback) {
    $current_data = [];
    $time_data = [];
    $total_remote_nodes = 0;

    foreach ($nodes_in as $node) {
        $host = $config[$node]['host'];

        // Skip if host is not connected/logged in
        if (!isset($ami_servers[$host]) || !isset($ami_connections[$host])) {
            continue;
        }

        $fp_host = $ami_connections[$host];

        // Get raw node data including connections
        // Pass $sendEventCallback for error reporting within getNode
        $connectedNodesRaw = getNode($fp_host, $node, $sendEventCallback);

        // Get local node info (like uptime from original getAstInfo)
        // Assuming getAstInfo returns an array or object with relevant info
        $localNodeInfo = getAstInfo($fp_host, $node); // From amifunctions.inc

        // Sort connected nodes (remote + local status entry '1')
        $sortedConnectedNodes = sortNodes($connectedNodesRaw);

        // Prepare time data structure for this node
        $time_data[$node]['node'] = $node;
        $time_data[$node]['info'] = $localNodeInfo; // Assuming this contains uptime etc.
        $time_data[$node]['remote_nodes'] = [];

        // Prepare current data structure for this node
        $current_data[$node]['node'] = $node;
        $current_data[$node]['info'] = $localNodeInfo; // Include static info here too
        $current_data[$node]['remote_nodes'] = [];

        $i = 0; // Index for remote nodes array (to prevent JS sorting by node number)
        foreach ($sortedConnectedNodes as $remoteNodeNum => $arr) {
            // Store time values in $time_data
            $time_data[$node]['remote_nodes'][$i]['elapsed'] = $arr['elapsed'] ?? 'N/A';
            // Use the formatted time string from sortNodes
            $time_data[$node]['remote_nodes'][$i]['last_keyed'] = $arr['last_keyed_formatted'] ?? 'Never';

            // Store non-time values (and placeholders) in $current_data
            $current_data[$node]['remote_nodes'][$i]['node'] = $arr['node'] ?? $remoteNodeNum;
            $current_data[$node]['remote_nodes'][$i]['info'] = $arr['info'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['link'] = $arr['link'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['ip'] = $arr['ip'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['direction'] = $arr['direction'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['keyed'] = $arr['keyed'] ?? 'n/a';
            $current_data[$node]['remote_nodes'][$i]['mode'] = $arr['mode'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['cos_keyed'] = $arr['cos_keyed'] ?? 0; // Default 0
            $current_data[$node]['remote_nodes'][$i]['tx_keyed'] = $arr['tx_keyed'] ?? 0; // Default 0
            $current_data[$node]['remote_nodes'][$i]['cpu_temp'] = $arr['cpu_temp'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['cpu_up'] = $arr['cpu_up'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['cpu_load'] = $arr['cpu_load'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['ALERT'] = $arr['ALERT'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['WX'] = $arr['WX'] ?? 'N/A';
            $current_data[$node]['remote_nodes'][$i]['LOGS'] = $arr['LOGS'] ?? 'N/A';

            // Add placeholders for time fields in the main data structure
            $current_data[$node]['remote_nodes'][$i]['elapsed'] = ' ';
            // Use 'Never' directly if applicable, otherwise placeholder
            $current_data[$node]['remote_nodes'][$i]['last_keyed'] = ($arr['last_keyed_formatted'] === 'Never') ? 'Never' : ' ';

            $i++;
        }
        $total_remote_nodes += count($sortedConnectedNodes); // Count nodes processed for this host node
    }

     // Calculate dynamic loop time based on total nodes being monitored across all connections
     // Add 1 for each main node being monitored itself.
     $j = $total_remote_nodes + count($nodes_in);
     // Original calculation: 20 - (j * 0.089). Ensure looptime is reasonable (e.g., >= 1)
     $looptime = max(1, intval(20 - ($j * 0.089)));


    return ['current_data' => $current_data, 'time_data' => $time_data, 'loop_time' => $looptime];
}

?>
