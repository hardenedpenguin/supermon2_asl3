<?php include("session.inc"); ?>
<?php include("header.inc"); ?>

<p>
    Welcome to <b><i><?php echo $CALL; ?></i></b> and associated AllStar nodes. This Bridge runs on the latest 
    <a href="https://allstarlink.org" target="_blank">AllStar V3</a> of AllStarLink.
</p>

<p>
    This Supermon2 web site is for monitoring and managing ham radio 
    <a href="http://allstarlink.org" target="_blank">AllStar</a> and app_rpt 
    node linking and micro-node.com RTCM clients. As of 2020, Micro-node has been shutdown. See the 
    <a href="http://crompton.com/hamradio/RTCM/" target="_blank">RTCM Info page here</a>.
</p>

<p>
    On the menu bar, click on the node numbers to see and manage (if you have a login ID) each local node. 
    These pages dynamically display any remote nodes that are connected to it. When a signal is received, 
    the remote node will move to the top of the list and will have a dark-blue background. The most recently 
    received nodes will always be at the top of the list.
</p>

<ul>
    <li>
        The <b>Dir</b> column shows <b>IN</b> when another node is connected to us and <b>OUT</b> if the 
        connection was made from us.
    </li>
    <li>
        The <b>Mode</b> column will show <b>Transceive</b> when this node will transmit and receive to/from 
        the connected node. It will show <b>Receive Only</b> or <b>Local Monitor</b> if this node only 
        receives from the connected node.
    </li>
</ul>

<p>
    Any Voter pages will show RTCM receiver details. The bars will move in near-real-time as the signal strength 
    varies. The voted receiver will turn green, indicating that it is being repeated. The numbers are the relative 
    signal strength indicator (RSSI), ranging from 0 to 255, a range of approximately 30dB. A value of zero means 
    that no signal is being selected. The color of the bars indicates the type of RTCM client, as shown on the key 
    below the voter display.
</p>

<p>
    Some changes to note. Please see the manual for complete install and update information.
</p>

<ul>
    <li>
        The primary new feature is the addition of dropdown menus. These menus help organize your system by managing 
        more and more clients. For example, you might have systems like Los Angeles, Las Vegas, San Francisco, and New York, 
        or you could put your nodes in one system, RTCMs in another, and hubs in yet another.
    </li>
    <li>
        Dropdowns are organized by the <code>system=</code> directive within <code>allmon.ini</code>. Items with no 
        <code>system=</code> directive will be shown on the navbar, as in v2.
    </li>
    <li>
        The INI file format has changed slightly:
        <ul>
            <li>Added the <code>system=</code> directive as mentioned above.</li>
            <li>RTCM's are now placed in the allmon INI with the <code>rtcmnode=</code> directive.</li>
            <li>The INI <code>[break]</code> stanza is non-operational and is ignored.</li>
            <li>Updated <code>allmon.ini.example</code> to reflect these changes.</li>
        </ul>
    </li>
    <li>The voter INI file (<code>voter.ini.php</code>) is no longer used and will be ignored if it exists.</li>
    <li>The login/logout link has been moved above the navbar and to the lower right corner of the header.</li>
    <li>A click on the page title will fetch the Allmon index page.</li>
    <li>The about page text has been moved to the index page.</li>
    <li>For the latest changes, see the Supermon2 manual.</li>
</ul>

<br>

<?php include("footer.inc"); ?>
