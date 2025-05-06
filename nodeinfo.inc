<?php

/**
 * Looks up IRLP node information using a cache or external command.
 * Handles regular IRLP nodes and reflector sub-channels (e.g., 9000 series).
 *
 * @global array $irlp_cache Cache for IRLP node lookups [lookup_node => [expiry_time, callsign, qth]].
 * @global string $IRLP_CALLS Path to the IRLP node list file (gzipped).
 * @global string $ZCAT Path to the zcat executable.
 * @global string $AWK Path to the awk executable.
 *
 * @param int|string $irlpnode The IRLP node number, potentially prefixed with '8'.
 * @return string Formatted string containing Call Sign, Node Number, and QTH, or a "No info" message.
 */
function irlp_cache_lookup($irlpnode)
{
    global $irlp_cache;
    global $IRLP_CALLS, $ZCAT, $AWK;

    $lookup = (int) ltrim((string)$irlpnode, '80');
    $lookup2 = (int)$irlpnode;
    $lookup_str = (string)$lookup;
    $lookup3 = (strlen($lookup_str) >= 4 && $lookup >= 9000) ? substr($lookup_str, 3, 1) : '0';

    if (isset($irlp_cache[$lookup])) {
        $column = $irlp_cache[$lookup];
        $time = time();

        if ($time > $column[0]) {
            unset($irlp_cache[$lookup]);
        } else {
            $display_call = $column[1];
            $display_node = $lookup;

            if ($lookup >= 9000 && $lookup3 > 0 && $column[1] !== "No info") {
                 $display_call = "REF" . $lookup2;
                 $display_node = $lookup2;
            }
            elseif ($lookup >= 9000 && $lookup3 > 0 && $column[1] === "No info") {
                 $display_node = $lookup2;
            }
            else {
                 $display_node = $lookup;
            }

            $info = $display_call . " [IRLP $display_node] " . $column[2];
            return $info;
        }
    }

    $lookup_search = $lookup;
    if ($lookup >= 9000 && $lookup3 > 0) {
        $lookup_search_str = (string)$lookup;
        if (strlen($lookup_search_str) >= 4) {
             $lookup_search = (int)(substr_replace($lookup_search_str, '0', 3, 1));
        }
    }

    $escaped_calls_file = escapeshellarg($IRLP_CALLS);
    $escaped_awk_script = escapeshellarg('-F|') . " " . escapeshellarg('BEGIN{IGNORECASE=1} $1 == ' . $lookup_search . ' {printf ("%s\\x18", $0); exit}');

    $command = "$ZCAT $escaped_calls_file | $AWK $escaped_awk_script";
    $res = `$command`;

    $table = explode("\x18", rtrim($res, "\x18"));

    $found = false;
    if (!empty($table[0])) {
        $row = $table[0];
        $column_data = explode("|", $row);

        if (count($column_data) >= 5) {
            $node_from_file = trim($column_data[0]);
            $call = trim($column_data[1]);
            $qth = trim($column_data[2] . ", " . $column_data[3] . " " . $column_data[4]);

            if ((int)$node_from_file == $lookup_search) {
                $cache_entry = [];
                $cache_entry[0] = time() + 600;
                $cache_entry[1] = $call;
                $cache_entry[2] = $qth;

                $irlp_cache[$lookup] = $cache_entry;

                $display_call = $call;
                $display_node = $lookup_search;

                if ($lookup >= 9000 && $lookup3 > 0) {
                    $display_call = "REF" . $lookup2;
                    $display_node = $lookup2;
                } else {
                    $display_node = $lookup;
                }

                $info = $display_call . " [IRLP $display_node] " . $qth;
                $found = true;
                return $info;
            }
        }
    }

    if (!$found) {
        $cache_entry = [];
        $cache_entry[0] = time() + 30;
        $cache_entry[1] = "No info";
        $cache_entry[2] = "No info";
        $irlp_cache[$lookup] = $cache_entry;

        $info = "No info [IRLP $lookup2] No info";
        return $info;
    }
}

/**
 * Looks up EchoLink node information via Asterisk Manager Interface (AMI), using a cache.
 *
 * @global array $elnk_cache Cache for Echolink node lookups [lookup_node => [expiry_time, callsign, status]].
 *
 * @param resource|object|null $fp The active Asterisk Manager Interface (AMI) connection handle, or null if unavailable.
 * @param int|string $echonode The EchoLink node number, potentially prefixed with '3'.
 * @return string Formatted string containing Call Sign, Node Number, and Status, or a "No info" or error message.
 */
function echolink_cache_lookup($fp, $echonode)
{
    global $elnk_cache;

    $lookup = (int) ltrim((string)$echonode, '30');

    if (isset($elnk_cache[$lookup])) {
        $column = $elnk_cache[$lookup];
        $time = time();

        if ($time > $column[0]) {
            unset($elnk_cache[$lookup]);
        } else {
            $info = $column[1] . " [EchoLink $lookup] (" . $column[2] . ")";
            return $info;
        }
    }

    if (!$fp) {
         return "Error: No AMI connection [EchoLink $lookup]";
    }
    $AMI = AMIcommand($fp, "echolink dbget nodename $lookup");
    $rows = explode("\n", trim($AMI));

    if (!empty($rows[0])) {
        $column_data = explode("|", $rows[0]);
        if (count($column_data) >= 3 && trim($column_data[0]) == $lookup) {
            $cache_entry = [];
            $cache_entry[0] = time() + 300;
            $cache_entry[1] = trim($column_data[1]);
            $cache_entry[2] = trim($column_data[2]);

            $elnk_cache[$lookup] = $cache_entry;
            $info = $cache_entry[1] . " [EchoLink $lookup] (" . $cache_entry[2] . ")";
            return $info;
        }
    }

    $cache_entry = [];
    $cache_entry[0] = time() + 30;
    $cache_entry[1] = "No info";
    $cache_entry[2] = "No info";
    $elnk_cache[$lookup] = $cache_entry;

    $info = "No info [EchoLink $lookup] (No info)";
    return $info;
}

/**
 * Retrieves descriptive information for a given node number.
 * Checks local Asterisk DB ($astdb global), then attempts EchoLink or IRLP lookups if applicable.
 * Also handles basic identification for other connection types.
 *
 * @global array $astdb Associative array containing local node information [nodeNum => [field1, field2, ...]].
 * @global array|null $node Contextual information about the current node (used for IP-based checks). Structure isn't fully defined here but expects ['ip']. May be null or not set.
 *
 * @param resource|object|null $fp The active Asterisk Manager Interface (AMI) connection handle (required for EchoLink lookups). Can be null if EchoLink lookup is not expected or available.
 * @param int|string $nodeNum The node number or identifier to look up.
 * @return string A descriptive string about the node (e.g., callsign/location, type of connection, or error message).
 */
function getAstInfo($fp, $nodeNum)
{
    global $astdb, $node;

    $info = 'Unknown Node';
    $numericNodeNum = filter_var($nodeNum, FILTER_VALIDATE_INT);

    if (isset($astdb[$nodeNum])) {
        $dbNode = $astdb[$nodeNum];
        $info = isset($dbNode[1]) ? trim($dbNode[1]) : '';
        $info .= isset($dbNode[2]) ? ' ' . trim($dbNode[2]) : '';
        $info .= isset($dbNode[3]) ? ' ' . trim($dbNode[3]) : '';
        $info = trim($info);
        if (empty($info)) {
             $info = "Node $nodeNum in local DB";
        }

    } elseif ($numericNodeNum !== false && $numericNodeNum > 3000000) {
        if ($fp) {
            $info = echolink_cache_lookup($fp, $nodeNum);
        } else {
            $info = "EchoLink Node $nodeNum (No AMI)";
        }

    } elseif ($numericNodeNum !== false && $numericNodeNum > 80000) {
        $info = irlp_cache_lookup($nodeNum);

    } elseif (isset($node['ip']) && !empty($node['ip']) && strlen(trim($node['ip'])) > 3) {
        $info = 'Web Txcvr or Phone Portal (' . htmlspecialchars($node['ip'], ENT_QUOTES) . ')';

    } elseif ($numericNodeNum !== false) {
        $info = 'Node not in database';

    } elseif (is_string($nodeNum) && preg_match('/-P$/', $nodeNum)) {
        $info = 'AllStar Phone Portal user';

    } else {
         $fallback_info = 'IAX Rpt client or other connection';
         if (isset($node['ip']) && !empty($node['ip']) && strlen(trim($node['ip'])) <= 3) {
              $fallback_info .= ' (Unknown Mode)';
         }
         if (is_scalar($nodeNum) && !empty($nodeNum)) {
              $info = $fallback_info . ' (' . htmlspecialchars($nodeNum, ENT_QUOTES) . ')';
         } else {
              $info = $fallback_info;
         }
    }

    return $info;
}


if (isset($fp) && $fp) {
    AMIlogoff($fp);
}

?>
