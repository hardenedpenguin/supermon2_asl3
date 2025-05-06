<?php
include("session.inc");

$currentUser = $_SESSION['user'] ?? null;

if ($currentUser) {
    logoutUser($currentUser);
}

session_unset();
$_SESSION = [];

$_SESSION['sm61loggedin'] = false;
$_SESSION['user'] = "";

print "Logged out.";

/**
 * Logs the user logout action to a file if logging is enabled.
 *
 * Includes global configuration and common functions. Reads hostname and
 * current time (potentially using external commands) and formats a log
 * string. Appends the log string to the file specified by the $SMLOGNAME
 * global variable if the $SMLOG global variable is set to "yes".
 * Handles potential errors if external commands fail or $SMLOGNAME is not set.
 *
 * @param string|null $user The username of the user logging out. Can be null.
 * @return void This function does not return a value.
 */
function logoutUser(?string $user): void
{
    include("user_files/global.inc");
    include("common.inc");

    if (isset($SMLOG) && $SMLOG === "yes") {
        $hostname = exec("$HOSTNAME | $AWK -F '.' '{print $1}'");
        if ($hostname === false) {
            $hostname = 'unknown';
        }

        // Using PHP's date function for better portability and safety
        $myday = date('l, F j, Y T - H:i:s');

        $logUser = $user ?? 'unknown';
        $wrtStr = sprintf(
            "Supermon2<b> logout </b>Host-%s <b>user-%s </b>at %s\n",
            htmlspecialchars($hostname),
            htmlspecialchars($logUser),
            $myday
        );

        if (isset($SMLOGNAME)) {
            file_put_contents($SMLOGNAME, $wrtStr, FILE_APPEND | LOCK_EX);
        } else {
            error_log("SMLOGNAME is not defined. Cannot write logout log.");
        }
    }
}
