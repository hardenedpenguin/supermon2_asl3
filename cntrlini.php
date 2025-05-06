<?php

/**
 * Gets the path to the appropriate control panel INI file for a user.
 * 
 * This function checks if a user-specific INI file exists, and if so, returns
 * its path. If no specific INI file is found, it falls back to a default 
 * control panel INI file or a no-log INI file depending on the situation.
 * 
 * @param string $user The username to find the corresponding INI file.
 * @return string The full path to the appropriate INI file.
 */
function get_cntrl_ini_name($user)
{
    include("common.inc");

    $defaultIni = "$USERFILES/controlpanel.ini";
    $noLogIni = "cntrlnolog.ini";
    $controlPanelIni = "controlpanel.ini";

    $cntrlIniFile = "$USERFILES/cntrlini.inc";
    if (file_exists($cntrlIniFile)) {
        include($cntrlIniFile);
    }

    if (isset($CNTRLININAME) && isset($user)) {
        if (array_key_exists($user, $CNTRLININAME)) {
            if ($CNTRLININAME[$user] !== "") {
                return checkcntrlini($USERFILES, $CNTRLININAME[$user]);
            } else {
                return checkcntrlini($USERFILES, $noLogIni);
            }
        } else {
            return checkcntrlini($USERFILES, $noLogIni);
        }
    } else {
        return checkcntrlini($USERFILES, $controlPanelIni);
    }
}

/**
 * Checks if a specified INI file exists in a directory. If the file doesn't exist,
 * it returns the default control panel INI file path.
 * 
 * @param string $fdir The directory containing the INI files.
 * @param string $fname The filename of the INI file to check.
 * @return string The full path to the INI file if it exists, otherwise the default controlpanel.ini path.
 */
function checkcntrlini($fdir, $fname)
{
    $filePath = "$fdir/$fname";
    $defaultPath = "$fdir/controlpanel.ini";

    if (file_exists($filePath)) {
        return $filePath;
    } else {
        return $defaultPath;
    }
}

/**
 * Validates if the custom control INI configuration structure is defined.
 * 
 * This function checks if the $CNTRLININAME array exists, indicating that
 * the `cntrlini.inc` file was included and contains user-specific INI configurations.
 * 
 * @return bool True if the custom INI configuration structure ($CNTRLININAME) is defined, otherwise false.
 */
function cntrliniValid()
{
    include("common.inc");

    $cntrlIniFile = "$USERFILES/cntrlini.inc";
    if (file_exists($cntrlIniFile)) {
        include($cntrlIniFile);
    }

    if (isset($CNTRLININAME)) {
        return true;
    } else {
        return false;
    }
}

?>
