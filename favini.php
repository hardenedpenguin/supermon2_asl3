<?php

/**
 * Gets the path to the user's preferred INI file.
 *
 * This function attempts to retrieve a user-specific INI file based on the provided
 * username. It checks for a mapping in a configuration file (`favini.inc`), and
 * if no mapping is found or if the user is not properly mapped, it defaults to
 * a standard `favorites.ini` or `favnolog.ini` file.
 *
 * @param string $user The username to look up in the mapping array.
 * @return string The full path to the appropriate INI file, or the default favorites.ini.
 */
function get_fav_ini_name($user)
{
    include("common.inc");

    if (file_exists("$USERFILES/favini.inc")) {
        include("$USERFILES/favini.inc");
    }

    if (isset($FAVININAME) && isset($user)) {
        if (array_key_exists($user, $FAVININAME)) {
            if ($FAVININAME[$user] !== "") {
                return checkfavini($USERFILES, $FAVININAME[$user]);
            } else {
                return checkfavini($USERFILES, "favnolog.ini");
            }
        } else {
            return checkfavini($USERFILES, "favnolog.ini");
        }
    } else {
        return "$USERFILES/favorites.ini";
    }
}

/**
 * Checks if a specified INI file exists in the directory, otherwise returns the default.
 *
 * This function checks if the specified INI file exists in the provided directory.
 * If the file doesn't exist, it returns the default `favorites.ini` file instead.
 *
 * @param string $fdir The directory path to check for the INI file.
 * @param string $fname The filename to check for existence.
 * @return string The full path to the existing file or the default favorites.ini.
 */
function checkfavini($fdir, $fname)
{
    $filePath = "$fdir/$fname";

    if (file_exists($filePath)) {
        return $filePath;
    } else {
        return "$fdir/favorites.ini";
    }
}

/**
 * Checks if the favorite INI configuration array ($FAVININAME) is available.
 *
 * This function verifies if the `$FAVININAME` array has been defined and is available
 * for use after including the necessary configuration files. It checks for the 
 * existence of the `favini.inc` file and the `$FAVININAME` array.
 *
 * @return bool True if the `$FAVININAME` array is set, false otherwise.
 */
function faviniValid()
{
    include("common.inc");

    if (file_exists("$USERFILES/favini.inc")) {
        include("$USERFILES/favini.inc");
    }

    if (isset($FAVININAME)) {
        return true;
    } else {
        return false;
    }
}
?>
