<?php

// Include common configuration settings.
include("common.inc");

/**
 * Returns the path to the appropriate INI file based on the username.
 * Falls back to "nolog.ini" or "allmon.ini" if user is not mapped or invalid.
 *
 * @param string|null $user The username to look up.
 * @return string Absolute path to the determined INI file.
 */
function get_ini_name($user)
{
    global $USERFILES;

    $ININAME = null;
    $auth_ini_file = "$USERFILES/authini.inc";

    if (file_exists($auth_ini_file)) {
        include($auth_ini_file);
    }

    if (isset($ININAME) && is_array($ININAME) && isset($user)) {
        if (array_key_exists($user, $ININAME)) {
            if ($ININAME[$user] !== "" && is_string($ININAME[$user])) {
                return checkini($USERFILES, $ININAME[$user]);
            } else {
                return checkini($USERFILES, "nolog.ini");
            }
        } else {
            return checkini($USERFILES, "nolog.ini");
        }
    } else {
        return "$USERFILES/allmon.ini";
    }
}

/**
 * Validates the existence and readability of a given INI file.
 * Falls back to "allmon.ini" if the file is missing or invalid.
 *
 * @param string $fdir Directory path containing INI files.
 * @param string $fname INI filename to check.
 * @return string Full path to the validated or fallback INI file.
 */
function checkini($fdir, $fname)
{
    global $USERFILES;

    $fname_cleaned = basename($fname);
    if ($fname_cleaned !== $fname || empty($fname_cleaned) || $fname_cleaned === '.' || $fname_cleaned === '..') {
        error_log("checkini: Invalid filename provided: " . $fname);
        return "$USERFILES/allmon.ini";
    }

    $filepath = "$fdir/$fname_cleaned";

    if (file_exists($filepath) && is_readable($filepath)) {
        return $filepath;
    } else {
        if ($fname_cleaned !== 'allmon.ini' && $fname_cleaned !== 'nolog.ini') {
            error_log("checkini: Requested INI file not found '$filepath', falling back to allmon.ini");
        } elseif (!file_exists("$fdir/allmon.ini")) {
            error_log("checkini: Fallback INI file not found '$fdir/allmon.ini'");
        }
        return "$USERFILES/allmon.ini";
    }
}

/**
 * Checks if user-specific INI mapping is enabled.
 *
 * @return bool True if $ININAME is set and is an array, false otherwise.
 */
function iniValid()
{
    global $USERFILES;

    $ININAME = null;
    $auth_ini_file = "$USERFILES/authini.inc";

    if (file_exists($auth_ini_file)) {
        include($auth_ini_file);
    }

    return isset($ININAME) && is_array($ININAME);
}
?>
