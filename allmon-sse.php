<?php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');

date_default_timezone_set('America/Chicago');

require_once('config.php');
require_once('sse_handler.php');
require_once('common.inc');
require_once('amifunctions.inc');
require_once('ami_manager.php');
require_once('data_processor.php');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

try {
    $configData = load_all_config();
    $config = $configData['config'];
    $astdb = $configData['astdb'];
    $supiniFileName = $configData['supini'];
} catch (Exception $e) {
    send_sse_message('error', ['status' => 'Configuration Error: ' . $e->getMessage()]);
    exit;
}

$nodesInput = $_GET['nodes'] ?? null;

if ($nodesInput === null || trim($nodesInput) === '') {
    send_sse_message('error', ['status' => 'Unknown request! Missing nodes parameter.']);
    exit;
}

$passedNodeNames = array_filter(array_map('trim', explode(',', strip_tags($nodesInput))), 'strlen');

if (empty($passedNodeNames)) {
    send_sse_message('error', ['status' => 'No valid node names provided in nodes parameter.']);
    exit;
}

$nodes = [];
foreach ($passedNodeNames as $nodeName) {
    if (isset($config[$nodeName])) {
        $nodes[] = $nodeName;
    } else {
        send_sse_message('nodes', [
            'node' => $nodeName,
            'status' => "Node '$nodeName' is not found in configuration or is invalid."
        ]);
    }
}

if (empty($nodes)) {
     send_sse_message('error', ['status' => 'No valid (configured) nodes specified.']);
     exit;
}

$sendEventCb = function($event, $data) {
    send_sse_message($event, $data);
};

$connectionResult = connect_ami_hosts($nodes, $config, $sendEventCb);
$ami_connections = $connectionResult['fp'];
$ami_servers = $connectionResult['servers'];

if (empty($ami_servers)) {
    send_sse_message('error', ['status' => 'Could not connect or log in to any required Asterisk Managers.']);
    disconnect_ami_hosts($ami_connections);
    exit;
}

$saved_data = [];
$loop_counter = 0;

set_time_limit(0);

while (ob_get_level() > 0) {
    ob_end_flush();
}
if (function_exists('flush')) {
    flush();
}

while (true) {
    if (connection_aborted()) {
        error_log("SSE Client disconnected.");
        break;
    }

    $prepared_data = prepare_sse_data($nodes, $config, $ami_connections, $ami_servers, $sendEventCb);
    $current_data = $prepared_data['current_data'];
    $time_data = $prepared_data['time_data'];
    $loop_time_throttle = (int) ($prepared_data['loop_time'] ?? 25);

    if ($current_data !== $saved_data) {
        $saved_data = $current_data;
        send_sse_message('nodes', $current_data);
        send_sse_message('nodetimes', $time_data);
        $loop_counter = 0;
    } else {
        $loop_counter++;
        if ($loop_counter >= $loop_time_throttle) {
            send_sse_message('nodetimes', $time_data);
            $loop_counter = 0;
        }
    }

    usleep(200000);
}

error_log("SSE Stream closing (client disconnected or script ended).");
disconnect_ami_hosts($ami_connections);

exit;
?>