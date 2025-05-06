<?php
// config.php: Loads configuration and Allstar database

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include('nodeinfo.inc');
include("user_files/global.inc");
include("authini.php");

/**
 * Loads all necessary configurations.
 *
 * @return array {
 *     @var array $config Parsed allmon INI configuration.
 *     @var array $astdb Parsed Allstar database.
 *     @var string $supini Path to the loaded INI file.
 * }
 * @throws Exception If the INI file cannot be loaded.
 */
function load_all_config() {
    global $ASTDB_TXT; // From common.inc

    // Get user-specific INI file path
    if (!isset($_SESSION['user'])) {
         $supini = get_ini_name(null);
         error_log("Warning: Session user not set in config.php, attempting to load default INI.");
    } else {
        $supini = get_ini_name($_SESSION['user']);
    }

    // Read allmon INI file
    if (!file_exists($supini)) {
        die("Couldn't load required configuration file: $supini\n");
    }
    $config = parse_ini_file($supini, true);
    if ($config === false) {
        die("Error parsing configuration file: $supini\n");
    }

    // Get Allstar database file
    $astdb = [];
    if (isset($ASTDB_TXT) && file_exists($ASTDB_TXT)) {
        $fh = fopen($ASTDB_TXT, "r");
        if ($fh && flock($fh, LOCK_SH)) {
            while (($line = fgets($fh)) !== FALSE) {
                $arr = explode("|", trim($line));
                if (count($arr) > 0 && isset($arr[0])) {
                    $astdb[$arr[0]] = $arr;
                }
            }
            flock($fh, LOCK_UN);
            fclose($fh);
        } else {
            error_log("Warning: Could not open or lock Allstar DB file: $ASTDB_TXT");
        }
    } else {
         error_log("Warning: Allstar DB file not found or not defined: $ASTDB_TXT");
    }

    global $elnk_cache, $irlp_cache;
    $elnk_cache = array();
    $irlp_cache = array();

    return ['config' => $config, 'astdb' => $astdb, 'supini' => $supini];
}
?>
