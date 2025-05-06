<?php
// Ensure output buffering is started (needed to capture output from passthru)
if (ob_get_level() == 0) {
    ob_start();
}

include("session.inc");
include("authusers.php");

/**
 * Executes a shell command and displays both the command and its output in a styled HTML table.
 *
 * @param string $command The shell command to execute.
 */
function execute_command_in_table($command) {
    // Display the command being executed
    echo '<tr>';
    echo '<th class="command-header" colspan="2">';
    echo 'Command: ' . htmlspecialchars($command);
    echo '</th>';
    echo '</tr>';

    // Capture and display the output of the command
    echo '<tr>';
    echo '<td class="command-output" colspan="2">';
    echo '<pre>';

    // Start a new output buffer to capture passthru output
    ob_start();
    passthru($command . ' 2>&1');  // Run command and redirect stderr to stdout
    $output = ob_get_clean();  // Get output and end buffering

    // Escape output for safe HTML display
    echo htmlspecialchars($output);

    echo '</pre>';
    echo '</td>';
    echo '</tr>';

    // Force immediate output to browser
    flush();
    ob_flush();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPU and System Status</title>
    <style>
        body {
            background-color: #000;
            color: #FFF;
            font-family: 'Courier New', Courier, monospace;
            margin: 0;
            padding: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th.command-header {
            background-color: #1a1a1a;
            color: #6495ED;
            font-weight: bold;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid #444;
        }
        td.command-output {
            border: 1px solid #444;
            padding: 10px;
            vertical-align: top;
        }
        td.command-output pre {
            font-size: 14px;
            white-space: pre-wrap;
            word-wrap: break-word;
            margin: 0;
            padding: 0;
            color: #FFF;
            background-color: transparent;
        }
        .error-message {
            color: #FF4136;
            font-weight: bold;
            background-color: #111;
            padding: 10px 15px;
            border: 1px solid #FF4136;
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<?php
// Ensure the user is logged in and has the required authorization role
if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true && get_user_auth("CSTATUSER")) {
    echo '<table>';
    echo '<tbody>';

    // Execute a series of system commands to display server status
    execute_command_in_table("/usr/bin/date");
    execute_command_in_table("export TERM=vt100 && sudo /usr/local/sbin/supermon/ssinfo - ");
    execute_command_in_table("/usr/bin/ip a");
    execute_command_in_table("/usr/local/sbin/supermon/din");
    execute_command_in_table("export TERM=vt100 && sudo /bin/top -b -n1");

    echo '</tbody>';
    echo '</table>';
} else {
    echo '<div class="error-message">ERROR: You Must login and be authorized to use this function!</div>';
}

// Cleanly end output buffering if still active
if (ob_get_level() > 0) {
    ob_end_flush();
}
?>
</body>
</html>
