<?php
include("session.inc");
include("authusers.php");
include("common.inc");
include("user_files/global.inc");
include("favini.php");

if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("FAVUSER"))) {
    die('<div style="background-color: black; color: white; padding: 20px; text-align: center;">
            <h3>ERROR: You Must login to use the "Favorites Panel" function!</h3>
        </div>');
}

$node = @trim(strip_tags($_GET['node']));
if (!is_numeric($node)) {
    die("Please provide a properly formatted URI. (ie favorites.php?node=1234)");
}

$title = "AllStar $node Favorites Panel";

if ($_SESSION['sm61loggedin'] === true) {
    $FAVINI = get_fav_ini_name($_SESSION['user']);

    if (!file_exists($FAVINI)) {
        die("Couldn't load $FAVINI file.\n");
    }

    $cpConfig = parse_ini_file($FAVINI, true);

    $cpCommands = $cpConfig['general'];
    if (isset($cpConfig[$node])) {
        foreach ($cpConfig[$node] as $type => $arr) {
            if ($type == 'label') {
                foreach ($arr as $label) {
                    $cpCommands['label'][] = $label;
                }
            } elseif ($type == 'cmd') {
                foreach ($arr as $cmd) {
                    $cpCommands['cmd'][] = $cmd;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $title; ?></title>
<meta charset="utf-8">
<meta name="description" content="SuperMon Favorites Panel">
<meta name="keywords" content="allstar monitor, app_rpt, asterisk">
<meta name="author" content="Tim Sawyer, WD6AWP">
<link rel="stylesheet" href="supermon.css">
<link rel="stylesheet" href="js/jquery-ui.css">
<script src="js/jquery.min.js"></script>
<script src="js/jquery-ui.min.js"></script>
<script src="js/alertify.min.js"></script>
<link rel="stylesheet" href="js/alertify.core.css"/>
<link rel="stylesheet" href="js/alertify.default.css" id="toggleCSS"/>

<script>
$(document).ready(function() {
<?php if ($_SESSION['sm61loggedin'] !== true) { ?>
    alert('Must login to use the Control Panel.');
<?php } else { ?>
    $("#cpMain").show();
<?php } ?>

    $('#cpExecute').click(function() {
        var localNode = $('#localnode').val();
        var cpCommand = $('#cpSelect').val();
        $.get('controlserverfavs.php?node=' + localNode + '&cmd=' + cpCommand, function(data) {
            alertify.success(data);
        });
    });
});
</script>
</head>
<body>
<div id="header" style="background-image: url(<?php echo $BACKGROUND; ?>); background-color:<?php echo $BACKGROUND_COLOR; ?>; height: <?php echo $BACKGROUND_HEIGHT; ?> ">
    <div id="headerTitle-large"><i><?php echo "$CALL - $TITLE_LOGGED"; ?></i></div>
    <div id="header3Tag-large"><i><?php echo $title ?></i></div>
    <div id="header2Tag-large"><i><?php echo $TITLE3 ?></i></div>
    <div id="headerImg"><a href="https://www.allstarlink.org" target="_blank"><img src="allstarlink.jpg" width="70%" style="border: 0;" alt="AllStar Logo"></a></div>
</div>

<div id="cpMain" style="display:none;">
    <br>
    <center>Sending Command to node <?php echo $node ?></center>
    <br>
    Favorite (select one): 
    <select class="submit-large" name="cpSelection" id="cpSelect">
        <?php 
        for ($i = 0; $i < count($cpCommands['label']); $i++) {
            echo "<option value=\"" . $cpCommands['cmd'][$i] . "\">" . $cpCommands['label'][$i] . "</option>\n";
        }
        ?>
    </select>
    <input type="hidden" id="localnode" value="<?php echo $node ?>">
    <input type="button" class="submit-large" value="Send Command" id="cpExecute">
    <br><br>
    <div id="cpResult"></div>
</div>

<div style="position:absolute; bottom:0px; left:30%;">
    <center>
        <input type="button" class="submit-large" value="Close Window" onclick="self.close()">
        <br><br>
        <center>Using the <?php echo $FAVINI ?> file</center><br>
        <?php include "footer.inc"; ?>
    </center>
</div>
</body>
</html>
