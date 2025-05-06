<?php

include_once("authini.php");

session_start(); // Ensure session is started

$var1 = explode('/', $_SERVER['REQUEST_URI']);
$var2 = array_pop($var1);
$current_url = urldecode($var2);

// Get the INI file name based on the logged-in user
$SUPINI = get_ini_name($_SESSION['user']);

// Read the specific allmon INI file for the user
if (!file_exists($SUPINI)) {
    die("Couldn't load ini file: " . htmlspecialchars($SUPINI));
}

// Parse the INI file, processing sections
$config = parse_ini_file($SUPINI, true);

// Check if parsing failed or the file was empty/malformed
if ($config === false || count($config) == 0) {
    die("Check ini file " . htmlspecialchars($SUPINI) . " format or permissions.");
}

// Build the menu structure
$systems = array();
foreach ($config as $name => $data) {
    // Skip sections explicitly marked not for the menu
    if (isset($data['menu']) && $data['menu'] !== "1") {
        continue;
    }
    // Skip sections named 'break'
    if (strtolower($name) == 'break') {
        continue;
    }

    // Determine the menu group ('system') or default to 'MainNavBar'
    $sysName = 'MainNavBar';
    if (isset($data['system']) && !empty($data['system'])) {
        $sysName = $data['system'];
    }

    // Determine the URL for the menu item
    $url = '';
    if (isset($data['url'])) {
        $url = $data['url'];
    } elseif (isset($data['rtcmnode'])) {
        $url = "voter.php?node=" . urlencode($data['rtcmnode']);
    } elseif (isset($data['nodes'])) {
        $url = "link.php?nodes=" . urlencode($data['nodes']);
    } else {
        $url = "link.php?nodes=" . urlencode($name);
    }

    // Add the item to the structured systems array
    $systems[$sysName][$name]['url'] = $url;
}

// Determine Menu Styling Based on 'Show_Detail'
$MENU_FONT = "22px";
$MENU_PADDING = "12px 4px 12px 4px;";
if (isset($Show_Detail) && $Show_Detail == "1") {
    $MENU_FONT = "14px";
    $MENU_PADDING = "4px 2px 2px 4px;";
}

// Generate HTML Output for the Menu
$outBuf = "<div id=\"menu\">\n";
$outBuf .= "    <ul style=\"font-size: " . htmlspecialchars($MENU_FONT) . ";\">\n";

foreach ($systems as $sysName => $items) {
    $safeSysName = htmlspecialchars($sysName);

    if ($sysName == "MainNavBar") {
        // Output main navigation bar items
        $outBuf .= "        <li>";
        foreach ($items as $itemName => $itemValue) {
            $safeItemName = htmlspecialchars($itemName);
            $url = htmlspecialchars($itemValue['url']);
            $target = "";
            // Handle opening in new tab
            if (substr($url, -1) === '>') {
                $url = substr($url, 0, -1);
                $target = " target=\"_blank\"";
            }
            $class = ($current_url == $itemValue['url'] || $current_url == $url) ? 'active' : '';
            $outBuf .= " <a class=\"" . $class . "\" href=\"" . $url . "\"" . $target . ">" . $safeItemName . "</a>";
        }
        $outBuf .= "</li>\n";
    } else {
        // Output dropdown menus
        $outBuf .= "        <li class=\"dropdown\">\n";
        $outBuf .= "            <a href=\"#\" class=\"dropbtn\">" . $safeSysName . "</a>\n";
        $outBuf .= "            <div class=\"dropdown-content\">\n";
        foreach ($items as $itemName => $itemValue) {
            $safeItemName = htmlspecialchars($itemName);
            $url = htmlspecialchars($itemValue['url']);
            $target = "";
            // Handle opening in new tab
             if (substr($url, -1) === '>') {
                $url = substr($url, 0, -1);
                $target = " target=\"_blank\"";
            }
            $class = ($current_url == $itemValue['url'] || $current_url == $url) ? 'active' : '';
            $style = "padding: " . htmlspecialchars($MENU_PADDING);
            $outBuf .= "                <a class=\"" . $class . "\" style=\"" . $style . "\" href=\"" . $url . "\"" . $target . ">" . $safeItemName . "</a>\n";
        }
        $outBuf .= "            </div>\n        </li>\n";
    }
}

$outBuf .= "    </ul>\n";
$outBuf .= "</div>\n";
$outBuf .= "<div class=\"clearer\"></div>\n";

// Print the final generated HTML
print $outBuf;

?>
