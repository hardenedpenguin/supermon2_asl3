<?php
// config.php: Loads configuration and Allstar database

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// These includes are assumed to be necessary and define globals like $ASTDB_TXT
// and functions like get_ini_name().
include_once('nodeinfo.inc'); // Use include_once to prevent multiple inclusions if this file is included elsewhere
include_once("user_files/global.inc");
include_once("authini.php");

/**
 * Loads all necessary configurations.
 *
 * @return array {
 *     @var array $config Parsed allmon INI configuration.
 *     @var array $astdb Parsed Allstar database.
 *     @var string $supini Path to the loaded INI file.
 * }
 * @throws RuntimeException If a critical configuration file cannot be loaded or parsed.
 */
function load_all_config(): array {
    global $ASTDB_TXT; // From common.inc (presumably via user_files/global.inc)
    global $elnk_cache, $irlp_cache; // These are being initialized globally by this function

    // 1. Determine user-specific INI file path
    $user = $_SESSION['user'] ?? null;
    if ($user === null) {
        // Log a warning, but proceed with default.
        // This is less critical than the file not existing.
        error_log("Warning: Session user not set in config.php, attempting to load default INI.");
    }
    $supini = get_ini_name($user);

    // 2. Read allmon INI file
    if (!is_readable($supini)) { // is_readable also checks for existence
        throw new RuntimeException("Couldn't load required configuration file (not found or not readable): $supini");
    }
    
    // Suppress errors from parse_ini_file itself and check return value
    $config = @parse_ini_file($supini, true);
    if ($config === false) {
        throw new RuntimeException("Error parsing configuration file: $supini");
    }

    // 3. Parse Allstar database file
    $astdb = [];
    if (empty($ASTDB_TXT)) {
        error_log("Warning: Allstar DB file path (ASTDB_TXT) is not defined or empty.");
    } elseif (!is_readable($ASTDB_TXT)) {
        error_log("Warning: Allstar DB file not found or not readable: $ASTDB_TXT");
    } else {
        $fh = @fopen($ASTDB_TXT, "r"); // Suppress warning, check $fh
        if ($fh === false) {
            error_log("Warning: Could not open Allstar DB file for reading: $ASTDB_TXT");
        } else {
            if (flock($fh, LOCK_SH)) {
                while (($line = fgets($fh)) !== false) {
                    $trimmed_line = trim($line);
                    if ($trimmed_line === '') { // Skip empty or whitespace-only lines
                        continue;
                    }
                    $arr = explode("|", $trimmed_line);
                    // Ensure the key (first element) is not an empty string.
                    // This prevents issues if a line starts with "|" or is just "|".
                    if (isset($arr[0]) && $arr[0] !== '') {
                        $astdb[$arr[0]] = $arr;
                    }
                }
                flock($fh, LOCK_UN);
            } else {
                error_log("Warning: Could not acquire shared lock on Allstar DB file: $ASTDB_TXT");
            }
            fclose($fh); // Ensure file handle is closed
        }
    }

    // 4. Initialize caches (as per original logic)
    // These are global side-effects of this function.
    $elnk_cache = [];
    $irlp_cache = [];

    return ['config' => $config, 'astdb' => $astdb, 'supini' => $supini];
}

?>