<?php

// Headers must be sent first
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // Useful for Nginx proxying

// Core PHP settings
date_default_timezone_set('America/Chicago'); // Or load from config if preferred

// Order can matter based on dependencies
require_once('config.php');
require_once('sse_handler.php');
require_once('common.inc');
require_once('amifunctions.inc');
require_once('ami_manager.php');
require_once('data_processor.php');

// --- Initialisation ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Load Configuration
try {
    $configData = load_all_config();
    $config = $configData['config'];
    $astdb = $configData['astdb'];
    $supini = $configData['supini'];
} catch (Exception $e) {
    // Send error as SSE event if possible, otherwise just die
    send_sse_message('error', ['status' => 'Configuration Error: ' . $e->getMessage()]);
    exit;
}

// --- Input Validation ---
if (empty($_GET['nodes'])) {
    send_sse_message('error', ['status' => 'Unknown request! Missing nodes parameter.']);
    exit;
}

// Read and sanitize parameters
$passedNodes = explode(',', @trim(strip_tags($_GET['nodes'])));

// Sanity check: Filter nodes to only those present in our INI file
$nodes = [];
foreach ($passedNodes as $node) {
    $node = trim($node);
    if (!empty($node) && isset($config[$node])) {
        $nodes[] = $node;
    } else {
        send_sse_message('nodes', ['node' => $node, 'status' => "Node $node is not in $supini file or invalid."]);
    }
}

// Exit if no valid nodes were found
if (empty($nodes)) {
     send_sse_message('error', ['status' => 'No valid nodes specified or found in configuration.']);
     exit;
}

// --- AMI Connection ---
// Define the callback function for sending connection status updates via SSE
$sendEventCb = function($event, $data) {
    send_sse_message($event, $data);
};

// Connect to AMI hosts
$connectionResult = connect_ami_hosts($nodes, $config, $sendEventCb);
$ami_connections = $connectionResult['fp'];
$ami_servers = $connectionResult['servers'];

// Exit if no servers could be connected to
if (empty($ami_servers)) {
    send_sse_message('error', ['status' => 'Could not connect or log in to any required Asterisk Managers.']);
    disconnect_ami_hosts($ami_connections);
    exit;
}

// --- Main Event Loop ---
$saved_data = [];
$loop_counter = 0;

// Set time limit to indefinite (important for long-running SSE script)
set_time_limit(0);
while (ob_get_level() > 0) {
    ob_end_flush();
}

while (true) {
    if (connection_aborted()) {
        error_log("SSE Client disconnected.");
        break; // Exit loop
    }

    // Fetch, process, and prepare data for all monitored nodes
    $prepared_data = prepare_sse_data($nodes, $config, $ami_connections, $ami_servers, $sendEventCb);
    $current_data = $prepared_data['current_data'];
    $time_data = $prepared_data['time_data'];
    $loop_time_throttle = $prepared_data['loop_time'];

    // Send full node data only when it changes
    if ($current_data !== $saved_data) {
        $saved_data = $current_data;
        send_sse_message('nodes', $current_data);
        send_sse_message('nodetimes', $time_data);
        $loop_counter = 0;
    } else {
        // If data hasn't changed, increment counter for periodic time update
        $loop_counter++;
        if ($loop_counter >= $loop_time_throttle) {
            send_sse_message('nodetimes', $time_data);
            $loop_counter = 0; // Reset counter
        }
    }

    // Wait before next iteration
    usleep(500000);

}

// --- Cleanup ---
error_log("SSE Stream closing normally or due to client disconnect.");
disconnect_ami_hosts($ami_connections);

exit;

?>
