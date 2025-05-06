<?php
include("session.inc");
include('amifunctions.inc');
include("user_files/global.inc");
include("common.inc");
include("authusers.php");
include("authini.php");

$node = trim(strip_tags($_GET['node']));
$localnode = trim(strip_tags($_GET['localnode']));
$intnode = (int)$node;
$perm = @trim(strip_tags($_GET['perm']));
?>
<html>
<head>
    <link rel="stylesheet" type="text/css" href="astlookup.css">
    <title>Opening information for: <?php echo "$node"; ?></title>
</head>
<body>

<?php
if (($_SESSION['sm61loggedin'] === true) && (get_user_auth("ASTLKUSER"))) {
    $SUPINI = get_ini_name($_SESSION['user']);

    if (!file_exists($SUPINI)) {
        die("Couldn't load $SUPINI file.\n");
    }

    $config = parse_ini_file($SUPINI, true);

    if (!isset($config[$localnode])) {
        die("Node $localnode is not in $SUPINI file.");
    }

    if (($fp = AMIconnect($config[$localnode]['host'])) === FALSE) {
        die("Could not connect to Asterisk Manager.");
    }

    if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
        die("Could not login to Asterisk Manager.");
    }

    exec("/usr/local/sbin/supermon/getirlp");

    if ("$intnode" != "$node") {
        $node = strtok($node, '-');
        do_allstar_callsign_search($fp, $node, $localnode);

        if ($perm != "on") {
            do_echolink_callsign_search($fp, $node);
            do_irlp_callsign_search($node);
        }
    } else if ($intnode > 80000 && $intnode < 90000) {
        do_irlp_number_search($intnode);
    } else if ($intnode > 3000000) {
        do_echolink_number_search($fp, $intnode);
    } else {
        do_allstar_number_search($fp, $intnode, $localnode);
    }

    if ($call != "") {
        $justCall = strtok($call, '-');
        output_FCC_call_data($justCall);
    } else {
        if (!is_numeric($node)) {
            $justCall = strtok($node, '-');
            output_FCC_call_data($justCall);
        }
    }

    AMIlogoff($fp);

} else {
    print "<br><h3>ERROR: You Must login to use this function!</h3>";
}
?>

</body>
</html>

<?php
function do_allstar_callsign_search($fp, $lookup, $localnode) {
    global $ASTDB_TXT, $CAT, $AWK;

    echo format_title("AllStar Callsign Search for: \"$lookup\"");
    $res = `$CAT $ASTDB_TXT | $AWK '-F|' 'BEGIN{IGNORECASE=1} $2 == "$lookup" {printf ("%s\x18", $0);}'`;
    process_allstar_result($fp, $res, $localnode);
}

function do_allstar_number_search($fp, $lookup, $localnode) {
    global $ASTDB_TXT, $CAT, $AWK;

    echo format_title("AllStar Node Number Search for: \"$lookup\"");
    $res = `$CAT $ASTDB_TXT | $AWK '-F|' 'BEGIN{IGNORECASE=1} $1 == "$lookup" {printf ("%s\x18", $0);}'`;
    process_allstar_result($fp, $res, $localnode);
}

function process_allstar_result($fp, $res, $localnode) {
    global $GREP, $call;

    if ("$res" == "") {
        echo "<p style=\"font-size:20px;\">....Nothing Found....</p>";
        return;
    }

    $table = explode("\x18", $res);
    array_pop($table);

    foreach ($table as $row) {
        $column = explode("|", $row);
        $node = trim($column[0]);
        $call = trim($column[1]);
        $desc = trim($column[2]);
        $qth = trim($column[3]);

        $AMI2 = AMIcommand($fp, "rpt lookup $node");
        $array = explode(',', $AMI2);
        $N = "$array[1] $array[2] " . rtrim($array[3], "Node");

        $G = `echo -n "$N" | $GREP 'NOT FOUND'`;
        if (strlen($G) >= 9) {
            $N = "NOT FOUND";
        }

        echo "<p style=\"font-size:20px;\">$node $call<br>$desc $qth<br>$N</p>";
    }
}

function do_echolink_callsign_search($fp, $lookup) {
    global $AWK, $GREP, $MBUFFER;

    $AMI = AMIcommand($fp, "echolink dbdump");

    $cmd = "$GREP 'No such command' | $MBUFFER -q -Q -m 1M";
    $res = run_pipe_cmd($cmd, $AMI);

    if (strlen($res) < 14) {
        $lookup = strtok($lookup, '-');
        foreach (['', '-L', '-R'] as $suffix) {
            $call = $lookup . $suffix;
            echo format_title("EchoLink Callsign Search for: \"$call\"");

            $cmd = "$AWK '-F|' 'BEGIN{IGNORECASE=1} \$2 == \"$call\" {printf (\"%s\x18\", \$0);}' | $MBUFFER -q -Q -m 1M";
            $results = run_pipe_cmd($cmd, $AMI);
            process_echolink_result($results);
        }
    }
}

function do_echolink_number_search($fp, $echonode) {
    global $AWK, $GREP, $MBUFFER;

    $lookup = (int)substr("$echonode", 1);
    $AMI = AMIcommand($fp, "echolink dbdump");

    $cmd = "$GREP 'No such command' | $MBUFFER -q -Q -m 1M";
    $res = run_pipe_cmd($cmd, $AMI);

    if (strlen($res) < 14) {
        echo format_title("EchoLink Node Number Search for: \"$lookup\"");
        $cmd = "$AWK '-F|' 'BEGIN{IGNORECASE=1} \$1 == \"$lookup\" {printf (\"%s\x18\", \$0);}' | $MBUFFER -q -Q -m 1M";
        $results = run_pipe_cmd($cmd, $AMI);
        process_echolink_result($results);
    }
}

function process_echolink_result($res) {
    global $call;

    if ($res == "") {
        echo "<p style=\"font-size:20px;\">....Nothing Found....</p>";
        return;
    }

    $table = explode("\x18", $res);
    array_pop($table);

    foreach ($table as $row) {
        $column = explode("|", $row);
        $node = trim($column[0]);
        $call = trim($column[1]);
        $ipaddr = trim($column[2]);

        echo "<p style=\"font-size:20px;\">$node&nbsp;&nbsp;$call&nbsp;&nbsp;$ipaddr</p>";
    }
}

function do_irlp_callsign_search($lookup) {
    global $IRLP_CALLS, $ZCAT, $AWK;

    echo format_title("IRLP Callsign Search for: \"$lookup\"");
    $res = `$ZCAT $IRLP_CALLS | $AWK '-F|' 'BEGIN{IGNORECASE=1} $2 == "$lookup" {printf ("%s\x18", $0);}'`;
    process_irlp_result($res);
}

function do_irlp_number_search($irlpnode) {
    global $IRLP_CALLS, $ZCAT, $AWK;

    $lookup = (int)substr("$irlpnode", 1);
    echo format_title("IRLP Node Number Search for: \"$lookup\"");
    $res = `$ZCAT $IRLP_CALLS | $AWK '-F|' 'BEGIN{IGNORECASE=1} $1 == "$lookup" {printf ("%s\x18", $0);}'`;
    process_irlp_result($res);
}

function process_irlp_result($res) {
    if ($res == "") {
        echo "<p style=\"font-size:20px;\">....Nothing Found....</p>";
        return;
    }

    $table = explode("\x18", $res);
    array_pop($table);

    foreach ($table as $row) {
        $column = explode("|", $row);
        $node = trim($column[0]);
        $call = trim($column[1]);
        $qth = trim($column[2] . ", " . $column[3] . " " . $column[4]);

        echo "<p style=\"font-size:20px;\">$node  $call<br>$qth</p>";
    }
}

function output_FCC_call_data($call) {
    echo "<ul>";
    echo "<li style=\"font-size:20px;\"><a href=\"https://www.qth.com/callsign.php?cs=$call\">Show FCC Call lookup for $call</a></li>";
    echo "<li style=\"font-size:20px;\"><a href=\"https://haminfo.tetranz.com/map/$call\">Lookup Hams near you</a></li>";
    echo "<li style=\"font-size:20px;\"><a href=\"http://allstarmap.org/\">Allstar Site Map</a></li>";
    echo "</ul>";
}

function run_pipe_cmd($cmd, $input) {
    $descriptorspec = [
        0 => ["pipe", "r"],
        1 => ["pipe", "w"],
        2 => ["file", "/dev/null", "w"]
    ];

    $process = proc_open($cmd, $descriptorspec, $pipes);

    if (is_resource($process)) {
        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        proc_close($process);
        return $output;
    }

    return '';
}

function format_title($text) {
    $i = strlen($text);
    $dashes = substr(str_repeat('-', 80), 0, 80 - $i);
    return "<p style=\"font-size:20px;\">$text$dashes</p>";
}
?>
