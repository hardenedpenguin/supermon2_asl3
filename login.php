<?php
/**
 * User Login Script
 * Handles user authentication, session management, and logging.
 */

// Ensure session_start() is called here (usually within session.inc)
include("session.inc");

if (isset($_SESSION['sm61loggedin']) && $_SESSION['sm61loggedin'] === true) {
    echo "Welcome back, " . htmlspecialchars($_SESSION['user']) . "! You are logged in.";
    echo "<br><a href='logout.php'>Logout</a>";
    exit;
}

$login_error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['user'] ?? '';
    $passwd = $_POST['passwd'] ?? '';

    if (http_authenticate($user, $passwd)) {
        session_regenerate_id(true);

        $_SESSION['sm61loggedin'] = true;
        $_SESSION['user'] = $user;

        logUser($_SESSION['user'], true);

        $requestUriParts = explode('/', $_SERVER['REQUEST_URI'] ?? '');
        $currentScript = array_pop($requestUriParts);
        $redirectUrl = urldecode($currentScript ?: 'index.php');

        header("Location: " . htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'));
        exit;

    } else {
        $login_error_message = "Sorry, login failed.";
        logUser($user, false);
    }
}

// --- Display Login Form (if not logged in) ---
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        .error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Please Log In</h1>
    <?php if (!empty($login_error_message)): ?>
        <p class="error"><?php echo $login_error_message; ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div>
            <label for="user">Username:</label>
            <input type="text" id="user" name="user" required value="<?php echo isset($user) ? htmlspecialchars($user) : ''; ?>">
        </div>
        <div>
            <label for="passwd">Password:</label>
            <input type="password" id="passwd" name="passwd" required>
        </div>
        <div>
            <button type="submit">Login</button>
        </div>
    </form>
</body>
</html>

<?php

/**
 * Implements Apache's APR1-MD5 password hashing algorithm.
 * Needed for compatibility with older .htpasswd files.
 *
 * **DEPRECATION WARNING:** APR1-MD5 is cryptographically weak. Migrate passwords.
 *
 * @param string $plainpasswd The plain text password.
 * @param string|null $saltstr The full salt string starting with '$apr1$'.
 * @return string The hashed password string or "FAIL" on error.
 */
function crypt_apr1_md5(string $plainpasswd, string $saltstr = null): string {
    if ($saltstr === null || strncmp($saltstr, '$apr1$', 6) !== 0) {
        return "FAIL";
    }
    $salt = substr($saltstr, 6, 8);
    $len = strlen($plainpasswd);
    $text = $plainpasswd . '$apr1$' . $salt;
    $bin = pack("H32", md5($plainpasswd . $salt . $plainpasswd));
    for ($i = $len; $i > 0; $i -= 16) {
        $text .= substr($bin, 0, min(16, $i));
    }
    for ($i = $len; $i > 0; $i >>= 1) {
        $text .= ($i & 1) ? chr(0) : $plainpasswd[0];
    }
    $bin = pack("H32", md5($text));
    for ($i = 0; $i < 1000; $i++) {
        $new = ($i & 1) ? $plainpasswd : $bin;
        if ($i % 3) $new .= $salt;
        if ($i % 7) $new .= $plainpasswd;
        $new .= ($i & 1) ? $bin : $plainpasswd;
        $bin = pack("H32", md5($new));
    }
    $tmp = '';
    for ($i = 0; $i < 5; $i++) {
        $k = $i + 6;
        $j = $i + 12;
        if ($j == 16) $j = 5;
        $tmp = $bin[$i] . $bin[$k] . $bin[$j] . $tmp;
    }
    $tmp = chr(0) . chr(0) . $bin[11] . $tmp;
    $tmp = strtr(
        strrev(substr(base64_encode($tmp), 2)),
        "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
        "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz"
    );
    return '$apr1$' . $salt . '$' . $tmp;
}

/**
 * Authenticates a user against a password file (typically .htpasswd).
 * Supports modern hashes via password_verify(), legacy APR1-MD5, and others.
 *
 * @param string $user The username to authenticate.
 * @param string $pass The plain text password to check.
 * @param string $pass_file The path to the password file. Defaults to '.htpasswd'.
 * @return bool True if authentication succeeds, False otherwise.
 */
function http_authenticate(string $user, string $pass, string $pass_file = '.htpasswd'): bool {
    if (empty($user) || empty($pass)) { return false; }
    if (!file_exists($pass_file) || !is_readable($pass_file)) {
        error_log("http_authenticate: Password file missing or not readable: " . $pass_file);
        return false;
    }
    $fp = fopen($pass_file, 'r');
    if (!$fp) {
        error_log("http_authenticate: Failed to open password file: " . $pass_file);
        return false;
    }
    $authenticated = false;
    while (($line = fgets($fp)) !== false) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') { continue; }
        $parts = explode(':', $line, 2);
        if (count($parts) !== 2) { continue; }
        list($fuser, $fpass) = $parts;
        if ($fuser === $user) {
            if (password_verify($pass, $fpass)) { $authenticated = true; break; }
            elseif (strncmp($fpass, '$apr1$', 6) === 0 && crypt_apr1_md5($pass, $fpass) === $fpass) { $authenticated = true; break; }
            elseif (crypt($pass, $fpass) === $fpass) { $authenticated = true; break; }
            $authenticated = false; break;
        }
    }
    fclose($fp);
    return $authenticated;
}

/**
 * Logs user login attempts (success or failure) to a configured file.
 * Relies on variables/constants possibly defined in included files.
 *
 * @param string $user The username used in the attempt.
 * @param bool $mode True for success, False for failure.
 */
function logUser(string $user, bool $mode): void {
    @include_once("user_files/global.inc");
    @include_once("common.inc");
    @include_once("authini.php");
    if (isset($SMLOG) && $SMLOG === "yes" && isset($SMLOGNAME) && !empty($SMLOGNAME)) {
        $type = $mode ? "Success" : "Fail";
        $logUsername = htmlspecialchars($user, ENT_QUOTES, 'UTF-8');
        $iniFile = 'unknown';
        if (function_exists('get_ini_name')) { $iniFile = get_ini_name($user); }
        $hostname = php_uname('n');
        $myday = date('l, F j, Y T - H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown_ip';
        $wrtStr = sprintf(
            "Supermon2 <b>login %s</b> Host-%s <b>user-%s</b> at %s from IP-%s using ini file-%s\n",
            $type, htmlspecialchars($hostname, ENT_QUOTES, 'UTF-8'), $logUsername,
            htmlspecialchars($myday, ENT_QUOTES, 'UTF-8'), htmlspecialchars($ip, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($iniFile, ENT_QUOTES, 'UTF-8')
        );
        $write_result = file_put_contents($SMLOGNAME, $wrtStr, FILE_APPEND | LOCK_EX);
        if ($write_result === false) { error_log("logUser: Failed to write to log file: " . $SMLOGNAME); }
    } elseif (isset($SMLOG) && $SMLOG === "yes" && (empty($SMLOGNAME) || !isset($SMLOGNAME))) {
         error_log("logUser: Logging is enabled (SMLOG=yes) but SMLOGNAME is not set or is empty.");
    }
}

?>
