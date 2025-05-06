<?php

include("session.inc");
include("authusers.php");

// Ensure the user is logged in via Supermon session AND has the specific permission 'SUSBUSER'
if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("SUSBUSER"))) {
    // If not authorized, stop script execution and show an error message
    die("<br><h3>ERROR: You Must login to use the 'simpleusb-control function!</h3>");
}

// Get 'node' and 'localnode' from the URL query string
// Use trim() to remove leading/trailing whitespace
// Use strip_tags() as a basic measure against HTML injection
$Node = trim(strip_tags($_GET['node']));
$localnode = trim(strip_tags($_GET['localnode']));

?>
<!DOCTYPE html>
<html>
<head>
    <title>SimpleUSB Control - Node <?php echo htmlspecialchars($localnode); ?></title>
    <link type="text/css" rel="stylesheet" href="supermon.css">
</head>

<body style="background-color: black; color: white;">

    <p style="text-align: center; font-size: 26px;">
        <b>View/Control simpleusb<br>on node <?php echo htmlspecialchars($localnode); ?></b>
    </p>

    <div style="font-size: 18px; margin-left: 70px; margin-right: 70px;">
        <p>
            This function is used to change simpleusb configuration values from Supermon. The opening
            screen shows the current simpleusb settings which can be changed by selecting
            new values and clicking on the UPDATE button.
        </p>
        <br>
        <p>
            <b>USE CAUTION</b> when changing simpleusb settings as you also would when doing so in
            the Asterisk client. Setting items to wrong values could cause a connected device or radio to cease
            working or hang up a channel. In particular the COS (or CTCSS) settings should
            reflect a CLEAR state for COS composite when no one is transmitting to your node
            and PTT should be clear when your node is not transmitting.
        </p>
        <br>
        <p>
            For safety when changing devices and selecting UPDATE all other settings are ignored.
            First change to the desired device, then select UPDATE, then make the necessary changes
            and select UPDATE again. Devices are checked to see if they are actually attached to a physical
            node and noted in the display. If a device is not attached then the settings are meaningless
            and generally assume defaults or do not work. Both COS and PTT status rely on a screen refresh to show the current
            status. They do not dynamically change.
        </p>
        <br>
        <p>
            You may also use putty or ssh and login to your server and change or check these settings in the
            simpleusb-tune-menu selection.
        </p>
        <br>
        <br>
        <b><u>Setting Audio Levels</u></b>
        <br>
        <br>
        <p>
            The RX, TXA, and TXB levels have 0-999 ranges. The RX level is set in
            combination with RXBOOST. Turning RXBOOST on increases the level by 20dB when
            necessary. The RX Level controls what others hear when you talk.
            TX level A and TX level B set the transmit levels and control the audio you hear
            from your node. Typically only TX A is used. They can be set using log (default) or linear
            methods controlled by TX audio mode button. You also have the option of a fine TX level adjustment
            using TX DSP level. This has a range of 800-999 with the default=999. Most do not need a fine
            adjustment and would leave this set at 999. To use this feature set the desired TX mixer level
            slightly higher and lower the DSP setting from 999 down to the correct level. Note that the DSP
            level effects both the TX A and TX B levels equally.
        </p>
        <br>
        <p>
            Be sure to permanently write your settings once you have determined they are correct.
            To do so set the permanent write button to yes and select UPDATE.
            This will ensure that settings will stay the same through a restart or reboot.
        </p>
        <br>
        <br>

        <!-- Form to proceed to the actual control page -->
        <form action="simpleusb-control.php?localnode=<?php echo htmlspecialchars($localnode); ?>" method="post">
            <center> <!-- Note: <center> is deprecated, use CSS for centering instead if possible -->
                <input type="submit" class="submit-large" value="Continue">
                 
                <input type="button" class="submit-large" value="Close Window" onclick="self.close()">
            </center>
        </form>
    </div>

</body>
</html>
