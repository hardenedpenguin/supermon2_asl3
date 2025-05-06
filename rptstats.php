<?php

include("session.inc");
include("amifunctions.inc");
include("authusers.php");
include("common.inc");
include("authini.php");

$node = (int)trim(strip_tags($_GET['node']));
$localnode = (int)trim(strip_tags($_GET['localnode']));

if (($_SESSION['sm61loggedin'] === true) && ($node > 0) && (get_user_auth("RSTATUSER"))) {
    $title = "AllStar 'rpt stats' for node: $node";
} else {
    $title = "AllStar 'rpt stats' for node: $localnode";
}

// Check if the user is logged in and authorized
if (($_SESSION['sm61loggedin'] === true) && (get_user_auth("RSTATUSER"))) {
    if ($node) {
        // Temporary fix for status server issue 2/15/2021
        header("Location: http://stats.allstarlink.org/stats/$node");
    } else {
?>
        <html>
        <head>
            <link rel="stylesheet" type="text/css" href="style.css" />
            <title><?php echo "$title"; ?></title>
            <style>
                body {
                    background-color: black;
                    color: white;
                    font-family: Arial, sans-serif;
                }
                pre {
                    white-space: pre-wrap; 
                    word-wrap: break-word;
                }
                h3 {
                    color: red;
                }
            </style>
        </head>
        <body>
        <pre>
<?php
            // Select ini file based on username
            $SUPINI = get_ini_name($_SESSION['user']);

            // Check if the INI file exists
            if (!file_exists($SUPINI)) {
                die("Couldn't load $SUPINI file.\n");
            }

            // Parse the INI file
            $config = parse_ini_file($SUPINI, true);

            // Check if the node exists in the INI file
            if (!isset($config[$localnode])) {
                die("Node $node is not in $SUPINI file.");
            }

            // Set up Asterisk Manager Interface (AMI) connection
            if (($fp = AMIconnect($config[$localnode]['host'])) === FALSE) {
                die("Could not connect to Asterisk Manager.");
            }

            // Login to Asterisk Manager
            if (AMIlogin($fp, $config[$localnode]['user'], $config[$localnode]['passwd']) === FALSE) {
                die("Could not login to Asterisk Manager.");
            }

            // Show report statistics
            show_rpt_stats($fp, $localnode);

            // Ensure AMI logoff after operations are completed
            AMIlogoff($fp);
?>
        </pre>
        </body>
        </html>
<?php
    }
} else {
    echo ("<br><h3>ERROR: You Must login to use this function!</h3>");
}

function show_rpt_stats($fp, $node)
{
    // Fetch stats from AMI command
    $AMI1 = AMIcommand($fp, "rpt stats $node");

    // Clean up the stats output
    $stats = trim(`echo -n "$AMI1" | head --lines=-1`);
    if ("$stats") {
        echo "<p style=\"font-size:20px;\">$stats</p>";
    } else {
        echo htmlspecialchars("<NONE>") . "\n";
    }
}
?>
