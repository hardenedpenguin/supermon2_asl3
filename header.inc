<?php

require_once("user_files/global.inc");
require_once("common.inc");

// Default value for showing password checkbox
if (!isset($SHOWPW)) {
    $SHOWPW = true;
}

// Ensure session user is initialized
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "";
}
// Ensure login status is initialized
if (!isset($_SESSION['sm61loggedin'])) {
    $_SESSION['sm61loggedin'] = false;
}

// Process display data cookie if set
if (isset($_COOKIE['display-data'])) {
    foreach ($_COOKIE['display-data'] as $name => $value) {
        $name = htmlspecialchars($name);
        switch ($name) {
            case "number-displayed";
                $Displayed_Nodes = htmlspecialchars($value);
                break;
            case "show-number";
                $Display_Count = htmlspecialchars($value);
                break;
            case "show-all";
                $Show_All = htmlspecialchars($value);
                break;
            case "show-detailed";
                $Show_Detail = htmlspecialchars($value);
                break;
        }
    }
}

// Default to detailed display if not set in cookie or invalid
if (!isset($Show_Detail) || ($Show_Detail !== '0' && $Show_Detail !== '1')) {
    $Show_Detail = "1"; // Default to detailed view
}

// Set page title based on URI
$var1 = explode('/', $_SERVER['REQUEST_URI']);
$var2 = array_pop($var1);
$uri = urldecode($var2);

// Build page title
if (!empty($SMSERVERNAME)) {
    $pageTitle = $SMSERVERNAME . " | Supermon2 | ";
} else {
    $pageTitle = strtoupper(htmlspecialchars($_SERVER['SERVER_NAME'])) . " | Supermon 2 | ";
}
// Add specific page identifiers
if (preg_match("/voter\.php\?node=(\d+)$/", $uri, $matches)) {
    $pageTitle .= "RTCM " . htmlspecialchars($matches[1]);
} elseif (preg_match("/link\.php\?nodes=(.+)$/", $uri, $matches)) {
    $pageTitle .= htmlspecialchars($matches[1]);
} elseif (preg_match("/about/", $uri, $matches)) {
    $pageTitle .= "" . ucfirst(htmlspecialchars($matches[0]));
}

// Determine base path
$home = '/';
if (!empty($var1[1])) {
    $home = "/" . htmlspecialchars($var1[1]);
}

// Prepare classes for conditional styling
$body_classes = '';
if ($Show_Detail != "1") {
    $body_classes = ' view-compact';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="generator" content="By hand with vi text editor">
    <meta name="description" content="AllStar node manager">
    <meta name="keywords" content="allstar monitor, app_rpt, asterisk">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Tim Sawyer, WD6AWP">
    <meta name="mods" content="New features, IRLP capability, Paul Aidukas, KN2R">
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link type="text/css" rel="stylesheet" href="supermon.css">
    <link type="text/css" rel="stylesheet" href="js/jquery-ui.css">
    <link rel="stylesheet" href="js/alertify.core.css"/>
    <link rel="stylesheet" href="js/alertify.default.css" id="toggleCSS"/>

    <script src="js/jquery.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/alertify.min.js"></script>

    <script>
      var supermonConfig = {
        isLoggedIn: <?php echo json_encode($_SESSION['sm61loggedin'] === true); ?>,
        username: <?php echo json_encode($_SESSION['user']); ?>
      };
    </script>

    <script src="js/supermon_header.js"></script>

</head>

<?php
$body_style = "width: 100%; max-width: 880px; margin: auto;";
if (isset($DISPLAY_BACKGROUND)) {
    $body_style .= " background-color:" . htmlspecialchars($DISPLAY_BACKGROUND) . ";";
}
?>
<body class="<?php echo trim($body_classes); ?>" style="<?php echo $body_style; ?>">

<?php
// Prepare header style attribute
$header_style = "background-image: url('" . htmlspecialchars($BACKGROUND, ENT_QUOTES) . "'); " .
                "background-color: " . htmlspecialchars($BACKGROUND_COLOR) . "; " .
                "height: " . htmlspecialchars($BACKGROUND_HEIGHT) . ";";
?>
<header id="header" style="<?php echo $header_style; ?>">

    <?php
    // Display main title based on login status
    if ($_SESSION['sm61loggedin'] === true) {
        print "<div id=\"headerTitle\"><a href=\"index.php\"><span>" . htmlspecialchars($TITLE_LOGGED) . "</span></a></div>";
    } else {
        print "<div id=\"headerTitle\"><a href=\"index.php\"><span>" . htmlspecialchars($TITLE_NOT_LOGGED) . "</span></a></div>";
    }

    // Display callsign/link if MY_URL is set
    if (isset($MY_URL)) {
        $target = '';
        if (substr($MY_URL, -1) == ">") { // Handle target="_blank"
            $MY_URL = substr_replace($MY_URL, "", -1);
            $target = ' target="_blank" rel="noopener noreferrer"';
        }
        print "<div id=\"headerTitle2\"><a href=\"" . htmlspecialchars($MY_URL, ENT_QUOTES) . "\"" . $target . "><span>" . htmlspecialchars($CALL) . "</span></a></div>";
    } else {
        print "<div id=\"headerTitle2\"><span>" . htmlspecialchars($CALL) . "</span></div>";
    }

    // Display location and title 2
    print "<div id=\"headerTag\"><span>" . htmlspecialchars($LOCATION) . "<br>" . htmlspecialchars($TITLE2) . "</span></div>";

    // Display title 3 only if logged in
    if ($_SESSION['sm61loggedin'] === true) {
        print "<div id=\"header2Tag\"><span>" . htmlspecialchars($TITLE3) . "</span></div>";
    }
    ?>

    <!-- Logout Link Area -->
    <div id="headerLogout">
        <div id="headerLogout2">
            <a href="#" id="logoutlink"><span style="background-color:black">Logout</span></a>
        </div>
    </div>

    <!-- Login Link Area -->
    <div id="headerLogin">
        <a href="#" id="loginlink">Login</a>
    </div>

    <!-- Allstar Link Logo -->
    <div id="headerImg"><a href="https://www.allstarlink.org" target="_blank" rel="noopener noreferrer"><img src="allstarlink.jpg" width="70%" style="border: 0px;" alt="Allstar Link Logo"></a></div>

    <?php
    // Display custom logo if configured
    if (isset($LOGO_NAME, $LOGO_SIZE, $LOGO_POSITION_RIGHT, $LOGO_POSITION_TOP)) {
        $logo_style = "border: 0px; position:absolute; top:" . htmlspecialchars($LOGO_POSITION_TOP) . "; right:" . htmlspecialchars($LOGO_POSITION_RIGHT) . ";";
        $logo_img = "<img src=\"" . htmlspecialchars($LOGO_NAME, ENT_QUOTES) . "\" width=\"" . htmlspecialchars($LOGO_SIZE) . "\" style=\"" . $logo_style . "\" alt=\"Site Logo\">";

        if (isset($LOGO_URL)) {
            $target = '';
            if (substr($LOGO_URL, -1) == ">") { // Handle target="_blank"
                $LOGO_URL = substr_replace($LOGO_URL, "", -1);
                $target = ' target="_blank" rel="noopener noreferrer"';
            }
            print "<div><a href=\"" . htmlspecialchars($LOGO_URL, ENT_QUOTES) . "\"" . $target . ">" . $logo_img . "</a></div>";
        } else {
            print "<div>" . $logo_img . "</div>";
        }
    }
    ?>
</header>

<div class="clearer"></div>

<div style="position:absolute;left:40%;top:35%; display:none;" id="login">
    <form style="border-style:solid;border-radius:5px;" id="myform" action="" method="post" onsubmit="return false;">
        <fieldset style="width:45px; height:240px; background-color:black;">
            <label class="exit" style="float:right;clear:both;"><input type="radio" onclick="hideLogin()"/><span><b>X</b></span></label>
            <p style="font-size:1.5em;color:white;margin-top:1em;margin-bottom:.5em;"><b>Supermon2 Manager Login</b></p>
            <input style="font-weight:bold;margin-bottom:.5em;" type="text" id="user" placeholder="Username" autocapitalize="none" required>
            <br>
            <input style="font-weight:bold;" type="password" id="passwd" placeholder="Password" required>
            <?php
            if ($SHOWPW) {
                 print "<label style=\"font-weight:bold;color:white;\"><input style=\"margin-top:1em;\" id=\"checkbox\" type=\"checkbox\" onclick=\"showPW()\"> Show Password</label>";
            }
            ?>
            <br>
            <label style="font-weight:bold;color:white;"><input type="checkbox" style="font-weight:bold;margin-top:.5em;" onclick="clearForm()"> Clear</label>
            <input class="login" style="font-weight:bold;float:right;clear:both;margin-top:.5em;" type="button" name="submit" value="submit" onclick="validate_login()">
        </fieldset>
    </form>
</div>

<?php require_once("menu.inc"); ?>

<div id="test_area" style="display: none;"></div>

</body>
</html>
