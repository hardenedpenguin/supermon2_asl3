<?php

/**
 * Waits for and returns a complete AMI response block matching the given ActionID.
 *
 * @param resource $fp       Open socket connection to AMI.
 * @param string   $actionID The ActionID to look for.
 * @param int      $timeout  Optional overall timeout in seconds.
 * @return string|false      The matching response block or false on error/timeout.
 */
function AMIget_response($fp, $actionID, $timeout = 10)
{
    $startTime = time();
    $currentBlock = '';
    $blockActionIDFound = false;
    stream_set_timeout($fp, $timeout);

    while (true) {
        if ((time() - $startTime) > $timeout) {
            return false;
        }

        $line = fgets($fp);
        $metadata = stream_get_meta_data($fp);

        if ($line === false || $metadata['timed_out']) {
            return false;
        }

        $currentBlock .= $line;

        if (!$blockActionIDFound && stripos($line, "ActionID: $actionID") !== false) {
            $blockActionIDFound = true;
        }

        if (rtrim($line, "\r\n") === '') {
            if ($blockActionIDFound) {
                return $currentBlock;
            } else {
                $currentBlock = '';
            }
        }
    }

    return false;
}

/**
 * Opens a socket connection to the Asterisk Manager Interface (AMI).
 *
 * @param string $host    IP address and optional port (e.g., 127.0.0.1:5038).
 * @param int    $timeout Connection timeout in seconds.
 * @return resource|false AMI socket connection or false on failure.
 */
function AMIconnect($host, $timeout = 5)
{
    $arr = explode(":", $host);
    $ip = $arr[0];
    $port = isset($arr[1]) ? (int)$arr[1] : 5038;

    $fp = fsockopen($ip, $port, $errno, $errstr, $timeout);
    if (!$fp) {
        return false;
    }

    stream_set_timeout($fp, 2);
    $greeting = fgets($fp);
    if ($greeting === false || strpos($greeting, 'Asterisk Call Manager') === false) {
        @fclose($fp);
        return false;
    }

    return $fp;
}

/**
 * Logs in to the Asterisk Manager Interface with given credentials.
 *
 * @param resource $fp       AMI socket connection.
 * @param string   $user     AMI username.
 * @param string   $password AMI password.
 * @param int      $timeout  Optional timeout for the login response.
 * @return bool              True on successful login, false otherwise.
 */
function AMIlogin($fp, $user, $password, $timeout = 10)
{
    $actionID = 'login_' . $user . '_' . mt_rand();

    $loginCmd = "ACTION: LOGIN\r\n";
    $loginCmd .= "USERNAME: $user\r\n";
    $loginCmd .= "SECRET: $password\r\n";
    $loginCmd .= "EVENTS: off\r\n";
    $loginCmd .= "ActionID: $actionID\r\n\r\n";

    if (fwrite($fp, $loginCmd) === false) {
        return false;
    }

    $loginResponse = AMIget_response($fp, $actionID, $timeout);
    if ($loginResponse === false) {
        return false;
    }

    if (stripos($loginResponse, "Message: Authentication accepted") !== false &&
        stripos($loginResponse, "Response: Success") !== false) {
        return true;
    }

    return false;
}

/**
 * Sends a COMMAND action to AMI and returns the parsed output.
 *
 * @param resource $fp       AMI socket connection.
 * @param string   $cmdString The CLI command to execute.
 * @param int      $timeout   Optional timeout for the response.
 * @return string|false       Command output or false on failure.
 */
function AMIcommand($fp, $cmdString, $timeout = 10)
{
    $actionID = 'cmd_' . mt_rand() . '_' . time();

    $commandAction = "ACTION: COMMAND\r\n";
    $commandAction .= "COMMAND: $cmdString\r\n";
    $commandAction .= "ActionID: $actionID\r\n\r\n";

    if (fwrite($fp, $commandAction) === false) {
        return false;
    }

    $rawResponse = AMIget_response($fp, $actionID, $timeout);
    if ($rawResponse === false) {
        return false;
    }

    $responseLines = preg_split('/\\r\\n|\\n|\\r/', $rawResponse, -1, PREG_SPLIT_NO_EMPTY);
    $isError = false;

    foreach ($responseLines as $line) {
        $trimmedLine = trim($line);
        if (stripos($trimmedLine, 'Response: Error') === 0) {
            $isError = true;
            break;
        }
        if (stripos($trimmedLine, 'Output:') === 0 || $trimmedLine === '--END COMMAND--') {
            break;
        }
    }

    if ($isError) {
        return false;
    }

    $commandOutput = [];
    $outputPrefix = "Output: ";
    $prefixLength = strlen($outputPrefix);

    foreach ($responseLines as $line) {
        if (strncmp($line, $outputPrefix, $prefixLength) === 0) {
            $commandOutput[] = substr($line, $prefixLength);
        }
    }

    return implode("\n", $commandOutput);
}

/**
 * Logs off and closes the AMI connection.
 *
 * @param resource $fp AMI socket connection.
 * @return bool        True if successfully closed, false otherwise.
 */
function AMIlogoff($fp)
{
    if (!is_resource($fp) || get_resource_type($fp) !== 'stream') {
        return false;
    }

    $logoffCmd = "ACTION: Logoff\r\n";
    $logoffCmd .= "ActionID: logoff_" . mt_rand() . "\r\n\r\n";

    fwrite($fp, $logoffCmd);
    @fclose($fp);
    return true;
}

?>
