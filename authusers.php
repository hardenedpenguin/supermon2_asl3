<?php

/**
 * Checks if the currently logged-in user is authorized for a specific action.
 *
 * @param string $button Name of the variable (defined in authusers.inc) containing the list of authorized users.
 * @return bool True if authorized, false otherwise.
 */
function get_user_auth($button)
{
    include("common.inc");
    $auth_file = "$USERFILES/authusers.inc";

    if (file_exists($auth_file)) {
        include($auth_file);

        if (isset($$button) && is_array($$button)) {
            return in_array($_SESSION['user'], $$button, true);
        } else {
            return false;
        }
    } else {
        return true;
    }
}
