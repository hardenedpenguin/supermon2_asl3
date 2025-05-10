<?php
function sortNodes(array $nodes_input): array {
    $heard_node_times = [];
    $never_heard_node_data = [];

    foreach ($nodes_input as $nodeNum => $node_data_item) {
        $last_keyed_seconds = isset($node_data_item['last_keyed']) ? (int)$node_data_item['last_keyed'] : -1;

        if ($last_keyed_seconds === -1) {
            $never_heard_node_data[$nodeNum] = $node_data_item;
        } else {
            $heard_node_times[$nodeNum] = $last_keyed_seconds;
        }
    }

    asort($heard_node_times, SORT_NUMERIC);
    ksort($never_heard_node_data, SORT_NUMERIC);

    $sorted_nodes = [];
    foreach ($heard_node_times as $nodeNum => $seconds) {
        $node_data_to_sort = $nodes_input[$nodeNum];
        
        $h = floor($seconds / 3600);
        $m = floor(($seconds / 60) % 60);
        $s = $seconds % 60;
        $node_data_to_sort['last_keyed_formatted'] = sprintf("%03d:%02d:%02d", $h, $m, $s);
        
        $sorted_nodes[$nodeNum] = $node_data_to_sort;
    }

    foreach ($never_heard_node_data as $nodeNum => $node_data_to_sort) {
        $node_data_to_sort['last_keyed_formatted'] = "Never";
        $sorted_nodes[$nodeNum] = $node_data_to_sort;
    }

    return $sorted_nodes;
}

function prepare_sse_data(array $monitored_local_nodes, array $config, array $ami_connections, array $ami_servers, callable $sendEventCallback): array {
    $current_data = [];
    $time_data = [];
    $total_remote_nodes_processed = 0;

    foreach ($monitored_local_nodes as $local_node_num) {
        $host = $config[$local_node_num]['host'] ?? null;

        if ($host === null || !isset($ami_servers[$host]) || !isset($ami_connections[$host])) {
            continue;
        }

        $fp_host = $ami_connections[$host];
        $connected_nodes_raw = getNode($fp_host, $local_node_num, $sendEventCallback);
        $local_node_info = getAstInfo($fp_host, $local_node_num);
        $sorted_connected_nodes = sortNodes($connected_nodes_raw);

        $time_data[$local_node_num] = [
            'node' => $local_node_num,
            'info' => $local_node_info,
            'remote_nodes' => []
        ];
        $current_data[$local_node_num] = [
            'node' => $local_node_num,
            'info' => $local_node_info,
            'remote_nodes' => []
        ];
        
        $remote_node_idx = 0;
        foreach ($sorted_connected_nodes as $remote_node_key => $remote_node_data) {
            $time_data_entry = [
                'elapsed' => $remote_node_data['elapsed'] ?? 'N/A',
                'last_keyed' => $remote_node_data['last_keyed_formatted'],
            ];
            $time_data[$local_node_num]['remote_nodes'][$remote_node_idx] = $time_data_entry;

            $current_data_entry = [
                'node' => $remote_node_data['node'] ?? $remote_node_key,
                'info' => $remote_node_data['info'] ?? 'N/A',
                'link' => $remote_node_data['link'] ?? 'N/A',
                'ip' => $remote_node_data['ip'] ?? 'N/A',
                'direction' => $remote_node_data['direction'] ?? 'N/A',
                'keyed' => $remote_node_data['keyed'] ?? 'n/a',
                'mode' => $remote_node_data['mode'] ?? 'N/A',
                'cos_keyed' => $remote_node_data['cos_keyed'] ?? 0,
                'tx_keyed' => $remote_node_data['tx_keyed'] ?? 0,
                'cpu_temp' => $remote_node_data['cpu_temp'] ?? 'N/A',
                'cpu_up' => $remote_node_data['cpu_up'] ?? 'N/A',
                'cpu_load' => $remote_node_data['cpu_load'] ?? 'N/A',
                'ALERT' => $remote_node_data['ALERT'] ?? 'N/A',
                'WX' => $remote_node_data['WX'] ?? 'N/A',
                'LOGS' => $remote_node_data['LOGS'] ?? 'N/A',
                'elapsed' => ' ',
                'last_keyed' => ($remote_node_data['last_keyed_formatted'] === 'Never') ? 'Never' : ' ',
            ];
            $current_data[$local_node_num]['remote_nodes'][$remote_node_idx] = $current_data_entry;

            $remote_node_idx++;
        }
        $total_remote_nodes_processed += count($sorted_connected_nodes);
    }

     $total_monitored_entities = $total_remote_nodes_processed + count($monitored_local_nodes);
     $loop_time = max(1, (int)(20 - ($total_monitored_entities * 0.089)));

    return ['current_data' => $current_data, 'time_data' => $time_data, 'loop_time' => $loop_time];
}

?>