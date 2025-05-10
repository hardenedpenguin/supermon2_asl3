<?php

function connect_ami_hosts(array $nodes, array $config, callable $sendEventCallback): array {
    $servers = [];
    $fp = [];

    foreach ($nodes as $node) {
        if (!isset($config[$node])) {
            continue;
        }

        $host = $config[$node]['host'];

        if (!array_key_exists($host, $servers)) {
            $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Connecting to Asterisk Manager...']);

            $socket = AMIconnect($host);

            if ($socket === false) {
                $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Could not connect to Asterisk Manager.']);
                $fp[$host] = null;
            } else {
                $fp[$host] = $socket;
                if (AMIlogin($fp[$host], $config[$node]['user'], $config[$node]['passwd'])) {
                    $servers[$host] = 'y';
                } else {
                    $sendEventCallback('connection', ['host' => $host, 'node' => $node, 'status' => '   Could not login to Asterisk Manager.']);
                    fclose($fp[$host]);
                    $fp[$host] = null;
                }
            }
        }
    }
    $fp = array_filter($fp);
    return ['fp' => $fp, 'servers' => $servers];
}

function parseNode($fp, string $rptStatus, string $sawStatus): array {
    $curNodes = [];
    $modes = [];
    $longRangeLinks = [];

    $rxKeyed = '0';
    $txKeyed = '0';
    $cputemp = 'N/A';
    $cpuup = 'N/A';
    $cpuload = 'N/A';
    $ALERT = 'N/A';
    $WX = 'N/A';
    $LOGS = 'N/A';

    $parsedConns = [];

    $lines = explode("\n", $rptStatus);
    foreach ($lines as $line) {
        if (preg_match('/Conn: (.*)/', $line, $matches)) {
            $arr = preg_split("/\s+/", trim($matches[1]));
            if (count($arr) >= 5) {
                 if(is_numeric($arr[0]) && $arr[0] > 3000000) {
                     $parsedConns[] = [$arr[0], "", $arr[1], $arr[2], $arr[3], $arr[4] ?? null];
                 } else {
                     $parsedConns[] = $arr;
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
                 $longRangeLinks = preg_split("/,\s*/", trim($matches[1]));
             }
        }
    }

    foreach ($longRangeLinks as $linkLine) {
        if (!empty($linkLine) && strlen($linkLine) > 1) {
            $nodeNum = substr($linkLine, 1);
            $modes[$nodeNum]['mode'] = substr($linkLine, 0, 1);
        }
    }

    $keyups = [];
    $lines = explode("\n", $sawStatus);
    foreach ($lines as $line) {
        if (preg_match('/Conn: (.*)/', $line, $matches)) {
            $arr = preg_split("/\s+/", trim($matches[1]));
            if (count($arr) >= 4 && isset($arr[0])) {
                 $keyups[$arr[0]] = ['node' => $arr[0], 'isKeyed' => $arr[1], 'keyed' => $arr[2], 'unkeyed' => $arr[3]];
            }
        }
    }

    if (count($parsedConns) > 0) {
        foreach ($parsedConns as $nodeData) {
            if (!isset($nodeData[0])) continue;
            $n = $nodeData[0];

            $curNodes[$n]['node'] = $n;
            $curNodes[$n]['info'] = getAstInfo($fp, $n);

            $linkFromMode = null;
            if (isset($modes[$n]['mode'])) {
                $linkFromMode = ($modes[$n]['mode'] == 'C') ? "CONNECTING" : "ESTABLISHED";
            }

            $curNodes[$n]['ip'] = $nodeData[1] ?? 'N/A';
            $curNodes[$n]['direction'] = $nodeData[3] ?? 'N/A';
            $curNodes[$n]['elapsed'] = $nodeData[4] ?? 'N/A';
            $finalLink = $nodeData[5] ?? null;

            if ($finalLink === null) {
                $finalLink = $linkFromMode ?? "ESTABLISHED";

                $isEcholink = ($n > 3000000);
                $isStandardNodeWithExplicitNullLink = (!$isEcholink && isset($nodeData[5]) && $nodeData[5] === null);

                if ($isEcholink || $isStandardNodeWithExplicitNullLink) {
                    $curNodes[$n]['direction'] = $nodeData[2] ?? 'N/A';
                    $curNodes[$n]['elapsed'] = $nodeData[3] ?? 'N/A';
                }
            }
            $curNodes[$n]['link'] = $finalLink;
            $curNodes[$n]['mode'] = $modes[$n]['mode'] ?? 'Local RX';

            if (isset($keyups[$n])) {
                 $curNodes[$n]['keyed'] = ($keyups[$n]['isKeyed'] == 1) ? 'yes' : 'no';
                 $curNodes[$n]['last_keyed'] = $keyups[$n]['keyed'];
            } else {
                 $curNodes[$n]['keyed'] = 'n/a';
                 $curNodes[$n]['last_keyed'] = '-1';
            }

            $curNodes[$n]['cpu_temp'] = $cputemp;
            $curNodes[$n]['cpu_up'] = $cpuup;
            $curNodes[$n]['cpu_load'] = $cpuload;
            $curNodes[$n]['ALERT'] = $ALERT;
            $curNodes[$n]['WX'] = $WX;
            $curNodes[$n]['LOGS'] = $LOGS;
        }
    }

    $localTelemetry = [
        'cos_keyed' => ($rxKeyed === '1') ? 1 : 0,
        'tx_keyed' => ($txKeyed === '1') ? 1 : 0,
        'cpu_temp' => $cputemp,
        'cpu_up' => $cpuup,
        'cpu_load' => $cpuload,
        'ALERT' => $ALERT,
        'WX' => $WX,
        'LOGS' => $LOGS,
    ];

    if (isset($curNodes[1])) {
        $curNodes[1] = array_merge($curNodes[1], ['node' => 1], $localTelemetry);
    } else {
        $defaultNode1ConnectionData = [
            'info' => (count($parsedConns) == 0) ? "NO CONNECTION" : "Local Status",
            'ip' => 'N/A',
            'direction' => 'N/A',
            'elapsed' => 'N/A',
            'link' => 'N/A',
            'mode' => 'Local RX',
            'keyed' => 'n/a',
            'last_keyed' => '-1',
        ];
        $curNodes[1] = array_merge(['node' => 1], $defaultNode1ConnectionData, $localTelemetry);
    }

    return $curNodes;
}

function getNode($fp, $node, callable $sendEventCallback): array {
    $actionRand = mt_rand();
    $rptStatus = '';
    $sawStatus = '';

    $actionID_xstat = 'xstat' . $actionRand;
    $xstatCommand = "ACTION: RptStatus\r\nCOMMAND: XStat\r\nNODE: $node\r\nActionID: $actionID_xstat\r\n\r\n";
    if (fwrite($fp, $xstatCommand)) {
        $rptStatus = AMIget_response($fp, $actionID_xstat);
    } else {
        $sendEventCallback('error', ['node' => $node, 'status' => 'XStat AMI write failed!']);
        return [];
    }

    $actionID_sawstat = 'sawstat' . $actionRand;
    $sawstatCommand = "ACTION: RptStatus\r\nCOMMAND: SawStat\r\nNODE: $node\r\nActionID: $actionID_sawstat\r\n\r\n";
    if (fwrite($fp, $sawstatCommand)) {
        $sawStatus = AMIget_response($fp, $actionID_sawstat);
    } else {
        $sendEventCallback('error', ['node' => $node, 'status' => 'SawStat AMI write failed!']);
        return [];
    }

    return parseNode($fp, $rptStatus, $sawStatus);
}

function disconnect_ami_hosts(array $fp): void {
    foreach ($fp as $socket) {
        if ($socket && is_resource($socket)) {
            AMIlogoff($socket);
            fclose($socket);
        }
    }
}

?>