<?php
    include("session.inc");
    include("authusers.php");

    // Check if user is logged in and has the necessary authorization
    if (($_SESSION['sm61loggedin'] !== true) || (!get_user_auth("BANUSER"))) {
        die ("<br><h3>ERROR: You Must login to use the 'Allow/Restrict function' function!</h3>");
    }

    // Get node parameters from GET request, trim whitespace and remove tags
    $Node = trim(strip_tags($_GET['node']));
    $localnode = trim(strip_tags($_GET['localnode']));

?>

<html>
<head>
    <link type="text/css" rel="stylesheet" href="supermon.css">
    <title>Allow/Restrict AllStar Nodes Intro</title>
    <style>
        a:link, a:visited {
            color: #ADD8E6; /* Light blue */
        }
        a:hover, a:active {
            color: #87CEEB; /* Sky blue */
        }
        input[type="submit"].submit-large,
        input[type="button"].submit-large {
        }
    </style>
</head>

<body style="background-color: black; color: white;">

    <p style="text-align:center; font-size:1.5em;">
        <b>Allow/Restrict AllStar Nodes</b>
    </p>

    <div style="font-size:18px; margin-left:10px; margin-right:10px;">
        <p>
           This function can be used to temporarily or permanently block or
           allow remote nodes to connect to your node. Commonly called a
           blacklist or whitelist. Only one list can be in effect but both
           could be defined. You can either specify a list to allow (whitelist)
           or ban (blacklist). The blacklist is useful when there is an issue
           with a node that is causing problems at your end. This could be a
           node that has some technical issue that is keying or hanging up your
           node or perhaps someone who is not abiding by FCC or your rules.
           You should always try to contact the person you are blocking but in
           some cases that may not be possible. On the other hand a whitelist
           allows you to specify a list of nodes that CAN connect to your node.
           Only those nodes in the whitelist will be able to connect. In most
           situations you would use the blacklist blocking one or several nodes.
           If the blacklist is empty and active all nodes can connect, if the
           whitelist is empty and active no nodes can connect.
           <br><br>
           You MUST setup either the white or blacklist in each servers
           iax.conf you want to execute the ban on.
        </p>
        <p>
           The database name is either "whitelist" or "blacklist" You <b>MUST</b>
           configure your extension.conf and iax.conf files as described at
           this URL in order for this to work. NOTE newer Hamvoip installations
           already have ths setup but you need to activate in the iax.conf file.
        </p>
        <p>
           <a href="http://wiki.allstarlink.org/wiki/Blacklist_or_whitelist" target="_blank" rel="noopener noreferrer">
               http://wiki.allstarlink.org/wiki/Blacklist_or_whitelist
           </a>
           <br>
        </p>
    </div>

    <form action="node-ban-allow.php?ban-node=<?php echo htmlspecialchars($Node); ?>&localnode=<?php echo htmlspecialchars($localnode); ?>" method="post">
        <center>
            <input type="submit" class="submit-large" value="Continue">
             
            <input type="button" class="submit-large" Value="Close Window" onclick="self.close()">
        </center>
    </form>

</body>
</html>
