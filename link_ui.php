<?php
// File: link_ui.php (Revised for Mobile Structure Support)
// Generates the HTML user interface for the link page.
// Assumes corresponding CSS rules exist in styles.css and viewport meta tag is in the main HTML head.

global $nodes, $config, $astdb, $Show_Detail, $SUBMITTER, $SUBMIT_SIZE, $TEXT_SIZE;
global $WELCOME_MSG, $WELCOME_MSG_LOGGED;
global $system_type, $EXTN, $IRLPLOG, $DATABASE_TXT;
global $HAMCLOCK_ENABLED, $HAMCLOCK_URL;
global $user;
global $DVM_URL; // <-- Ensure DVM_URL is accessible globally

// --- Welcome Message ---
if ($_SESSION['sm61loggedin'] === false) {
    // Add class for CSS targeting
    if (isset($WELCOME_MSG) && !empty(trim($WELCOME_MSG))) {
        echo "<div class='welcome-message not-logged-in'>" . $WELCOME_MSG . "</div>\n";
    }
} else {
    // Add class for CSS targeting
    if (isset($WELCOME_MSG_LOGGED) && !empty(trim($WELCOME_MSG_LOGGED))) {
        echo "<div class='welcome-message logged-in'>" . $WELCOME_MSG_LOGGED . "</div>\n";
    }
}

// --- Connect Form (Only if logged in) ---
if ($_SESSION['sm61loggedin'] === true) {
?>
    <div id="connect_form"> <?php // ID used for styling the form container ?>
        <?php
        if (count($nodes) > 0) {
        ?>
            <?php // This inner div is targeted by CSS flexbox rules for mobile stacking ?>
            <div>
                <?php
                // Node Selection Dropdown/Hidden Input
                if (count($nodes) > 1) {
                    echo "<select id=\"localnode\" class=\"$SUBMIT_SIZE\">"; // Removed inline style
                    foreach ($nodes as $node) {
                        $node_display = htmlspecialchars($node);
                        $info_text = "Node not in database";
                        if (isset($astdb[$node]) && isset($astdb[$node][1])) {
                            $info_parts = array_filter(array_slice($astdb[$node], 1, 3));
                            $info_text = (!empty($info_parts) ? htmlspecialchars(implode(' ', $info_parts)) : '');
                        } elseif (isset($config[$node]['nvNodeInfo'])) {
                            $info_text = htmlspecialchars($config[$node]['nvNodeInfo']);
                        }
                        if ($info_text !== "Node not in database" && !empty($info_text)) {
                            $node_display .= " (" . $info_text . ")";
                        }
                        echo "<option class=\"$SUBMIT_SIZE\" value=\"" . htmlspecialchars($node) . "\"> " . $node_display . " </option>";
                    }
                    echo "</select>";
                } else {
                    echo "<input type=\"hidden\" id=\"localnode\" value=\"" . htmlspecialchars($nodes[0]) . "\">\n";
                }

                // Node Input & Permanent Checkbox
                // NOTE: Keeping dynamic inline style based on $Show_Detail. CSS may need !important to override on mobile if problematic.
                $input_style = ($Show_Detail != 1) ? "font-size:22px; width: 150px;" : "font-size:16px; width: 120px;";
                $input_style .= " vertical-align: middle; margin: 5px;"; // Added margin here
                $label_style = ($Show_Detail != 1) ? "font-size:22px;" : "font-size:16px;";
                $label_style .= " vertical-align: middle; color: white; margin: 5px;"; // Added margin here

                echo "<input style=\"$input_style\" type=\"text\" id=\"node\" placeholder=\"\" autocomplete=\"off\">";

                if (get_user_auth("PERMUSER")) {
                    echo "<label style=\"$label_style\"> Perm <input class=\"perm\" type=\"checkbox\" style=\"vertical-align: middle;\"> </label>";
                }
                ?>
            </div>

            <?php // Buttons Container - CSS will handle wrapping/stacking ?>
            <div class="connect-form-buttons">
                <?php
                $button_style = "margin: 3px 2px;"; // Simple spacing

                if (get_user_auth("CONNECTUSER")) { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Connect\" id=\"connect\">"; }
                if (get_user_auth("DISCUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Disconnect\" id=\"disconnect\">"; }
                if (get_user_auth("MONUSER"))     { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Monitor\" id=\"monitor\">"; }
                if (get_user_auth("LMONUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Local Monitor\" id=\"localmonitor\">"; }
                if (get_user_auth("DTMFUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"DTMF\" id=\"dtmf\">"; }
                if (get_user_auth("ASTLKUSER"))   { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Lookup\" id=\"astlookup\">"; }
                if (get_user_auth("RSTATUSER"))   { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Rpt Stats\" id=\"rptstats\">"; }
                if (get_user_auth("BUBLUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Bubble\" id=\"map\">"; }
                if (get_user_auth("CTRLUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"Control\" id=\"controlpanel\">"; }
                if (get_user_auth("FAVUSER"))     { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Favorites\" id=\"favoritespanel\">"; }

                if ($Show_Detail == 1) {
                    // Optional: Add a <hr class="button-separator"> instead of <br> if styled in CSS
                    echo "<hr class='button-separator' style='border: none; height: 1px; background-color: #555; margin: 10px 0;'>";

                    if (get_user_auth("CFGEDUSER"))   { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"Configuration Editor\" onclick=\"window.open('configeditor.php');\">"; }
                    if (get_user_auth("ASTRELUSER"))  { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"Iax/Rpt/DP RELOAD\" id=\"astreload\">"; }
                    if (get_user_auth("ASTSTRUSER"))  { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"AST START\" id=\"astaron\">"; }
                    if (get_user_auth("ASTSTPUSER"))  { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"AST STOP\" id=\"astaroff\">"; }
                    if (get_user_auth("FSTRESUSER"))  { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"RESTART\" id=\"fastrestart\">"; }
                    if (get_user_auth("RBTUSER"))     { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"Server REBOOT\" id=\"reboot\">"; }
                    if (get_user_auth("HWTOUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"AllStar How To's\" onclick=\"OpenHelp()\">"; }
                    if (get_user_auth("WIKIUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"AllStar Wiki\" onclick=\"OpenWiki()\">"; }
                    if (get_user_auth("CSTATUSER"))   { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"CPU Status\" id=\"cpustats\">"; }
                    if (get_user_auth("ASTATUSER"))   { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"AllStar Status\" id=\"stats\">"; }
                    if (get_user_auth("EXNUSER") && isset($EXTN) && $EXTN) { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Registry\" id=\"extnodes\">"; }
                    if (get_user_auth("NINFUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Node Info\" id=\"astnodes\">"; }
                    if (get_user_auth("ACTNUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Active Nodes\" onclick=\"OpenActiveNodes()\">"; }
                    if (get_user_auth("ALLNUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"All Nodes\" onclick=\"OpenAllNodes()\">"; }
                    if (get_user_auth("LLOGUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Linux Log\" id=\"linuxlog\">"; }
                    if (get_user_auth("ASTLUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"AST Log\" id=\"astlog\">"; }
                    if (get_user_auth("WLOGUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Web Access Log\" id=\"webacclog\">"; }
                    if (get_user_auth("WERRUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Web Error Log\" id=\"weberrlog\">"; }
                    if (get_user_auth("BANUSER"))     { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"Restrict\" id=\"openbanallow\">"; }
                    if (get_user_auth("DBTUSER") && isset($DATABASE_TXT) && !empty($DATABASE_TXT)) { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"Database\" id=\"database\">"; }
                    if (get_user_auth("GPIOUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMITTER\" value=\"GPIO\" id=\"openpigpio\">"; }
                    if (get_user_auth("IRLPLOGUSER") && isset($IRLPLOG) && $IRLPLOG) { echo "<input style=\"$button_style\" type=\"button\" class=\"submit\" value=\"IRLP Log\" id=\"irlplog\">"; }
                    // We will need to rewrite the entire php to support current asterisk versions.
                    //if (get_user_auth("SUSBUSER"))    { echo "<input style=\"$button_style\" type=\"button\" class=\"$SUBMIT_SIZE\" value=\"SimpleUSB\" id=\"opensimpleusb\">"; }
                }
                ?>
            </div> <?php // end connect-form-buttons ?>
            <script>
                // JS functions remain the same
                function OpenActiveNodes()  { window.open('http://stats.allstarlink.org'); }
                function OpenAllNodes()     { window.open('https://www.allstarlink.org/nodelist'); }
                function OpenHelp()         { window.open('https://wiki.allstarlink.org/wiki/Category:How_to'); }
                function OpenConfigEditor() { window.open('configeditor.php'); }
                function OpenWiki()         { window.open('http://wiki.allstarlink.org'); }
            </script>
        <?php
        } else {
            echo "<p style='color: white;'>No nodes configured or passed in the URL.</p>";
        }
        ?>
    </div>
<?php
} // End if logged in check

// --- Display Configuration / System Info Buttons ---
?>
<div class="config-info-buttons" style="text-align: center; margin-bottom: 15px;">
    <input type="button" class="<?php echo htmlspecialchars($SUBMIT_SIZE); ?>" Value="Display Configuration" onclick="window.open('display-config.php','DisplayConfiguration','status=no,location=no,toolbar=no,width=500,height=600,left=100,top=100')">

    <?php
    // --- Conditional Digital Dashboard Button ---
    // Check if $DVM_URL is set and not empty (trimming whitespace)
    if (isset($DVM_URL) && !empty(trim($DVM_URL))) {
    ?>
        <input type="button" class="<?php echo htmlspecialchars($SUBMIT_SIZE); ?>" Value="Digital Dashboard" onclick="window.open('<?php echo htmlspecialchars($DVM_URL); ?>','DigitalConfiguration','status=no,location=no,toolbar=no,width=940,height=890,left=10,top=10')">
    <?php
    } // --- End Conditional Button ---
    ?>

    <?php
    // --- System Info Button (conditional on login/auth) ---
    if (($_SESSION['sm61loggedin'] === true) && (get_user_auth("SYSINFUSER"))) {
        $WIDTH = ($Show_Detail == 1) ? 950 : 650;
        $HEIGHT = ($Show_Detail == 1) ? 550 : 750;
        // Using PHP echo for cleaner separation and concatenation
        echo " <input type=\"button\" class=\"" . htmlspecialchars($SUBMITTER) . "\" Value=\"System Info\" onclick=\"window.open('system-info.php','SystemInfo','status=no,location=no,toolbar=yes,width=" . $WIDTH . ",height=" . $HEIGHT . ",left=100,top=100')\">";
    }
    ?>
</div>


<?php // --- Node Tables --- ?>
<div id="list_link">
    <table class="fxwidth"> <?php // Removed inline style, use CSS for .fxwidth ?>
        <tbody> <?php // Added tbody for structure ?>
        <?php
        foreach ($nodes as $node) {
            // Prepare Node Info and Links (logic remains the same)
            $info = "Node not in database";
            if (isset($astdb[$node]) && isset($astdb[$node][1])) {
                $info_parts = array_filter(array_slice($astdb[$node], 1, 3));
                $info = !empty($info_parts) ? htmlspecialchars(implode(' ', $info_parts)) : '';
            } elseif (isset($config[$node]['nvNodeInfo'])) {
                $info = htmlspecialchars($config[$node]['nvNodeInfo']);
            }

            $nodeURL = ""; $bubbleChart = ""; $lsNodesChart = ""; $listenLiveLink = ""; $archiveLink = ""; $customNodeLink = ""; $customNodeLinkTarget = "_self";
            $custom_url_var = 'URL_' . $node;
            if (isset(${$custom_url_var}) && !empty(${$custom_url_var})) {
                $customNodeLink = ${$custom_url_var};
                if (substr($customNodeLink, -1) == ">") { $customNodeLink = substr($customNodeLink, 0, -1); $customNodeLinkTarget = "_blank"; }
            }
            $is_private = (isset($config[$node]['hideNodeURL']) && $config[$node]['hideNodeURL'] == 1);
            if (!$is_private && intval($node) >= 2000) {
                $nodeURL = "http://stats.allstarlink.org/nodeinfo.cgi?node=" . urlencode($node);
                $bubbleChart = "http://stats.allstarlink.org/getstatus.cgi?" . urlencode($node);
            }
            if (isset($config[$node]['lsnodes'])) { $lsNodesChart = $config[$node]['lsnodes']; }
            elseif (isset($config[$node]['host']) && (preg_match("/localhost/", $config[$node]['host']) || preg_match("/127\.0\.0\.1/", $config[$node]['host']))) { $lsNodesChart = "/cgi-bin/lsnodes_web?node=" . urlencode($node); }
            if (isset($config[$node]['listenlive'])) { $listenLiveLink = $config[$node]['listenlive']; }
            if (isset($config[$node]['archive'])) { $archiveLink = $config[$node]['archive']; }

            // Build Title String (logic remains the same)
            $title = " "; $title .= ($is_private) ? "Private Node " : "Node ";
            $node_number_display = htmlspecialchars($node);
            if (!empty($nodeURL)) { $title .= "<a href=\"" . htmlspecialchars($nodeURL) . "\" target=\"_blank\">" . $node_number_display . "</a>"; }
            elseif (!empty($customNodeLink) && $customNodeLinkTarget == '_self') { $title .= "<a href=\"" . htmlspecialchars($customNodeLink) . "\">" . $node_number_display . "</a>"; }
            else { $title .= $node_number_display; }
            $title .= " => ";
            if (!empty($customNodeLink)) { $title .= "<a href=\"" . htmlspecialchars($customNodeLink) . "\" target=\"" . $customNodeLinkTarget . "\">" . $info . "</a>"; }
            else { $title .= $info; }
            $title .= "  ";
            $links_line = [];
            if (!empty($bubbleChart))    { $links_line[] = "<a href=\"" . htmlspecialchars($bubbleChart) . "\" target=\"_blank\" id=\"bubblechart_$node\">Bubble Chart</a>"; }
            if (!empty($lsNodesChart))   { $links_line[] = "<a href=\"" . htmlspecialchars($lsNodesChart) . "\" target=\"_blank\" id=\"lsnodeschart_$node\">lsNodes</a>"; }
            if (!empty($listenLiveLink)) { $links_line[] = "<a href=\"" . htmlspecialchars($listenLiveLink) . "\" target=\"_blank\" id=\"listenlive_$node\">Listen Live</a>"; }
            if (!empty($archiveLink))    { $links_line[] = "<a href=\"" . htmlspecialchars($archiveLink) . "\" target=\"_blank\" id=\"archive_$node\">Archive</a>"; }
            if (!empty($links_line)) {
                 // Use class for the sub-links span
                $title .= "<br><span class='table-title-sublinks'>" . implode("    ", $links_line) . "</span>";
            }

            // Output Table Structure for the node
            ?>
            <tr>
                <?php // Add padding to this cell to create space BETWEEN node tables ?>
                <td class="node-table-container-cell" style="padding-bottom: 20px; border: none;">
                    <?php // This wrapper enables horizontal scrolling via CSS ?>
                    <div class="table-wrapper">
                        <?php
                        if ($Show_Detail == 1) { // Detailed View Table
                        ?>
                            <table class="gridtable node-table-detailed" id="table_<?php echo htmlspecialchars($node); ?>"> <?php // Removed inline styles ?>
                                <colgroup> <col> <col> <col> <col> <col> <col> <col> </colgroup> <?php // Let CSS/content define widths ?>
                                <thead>
                                    <tr>
                                        <th colspan="7" class="table-title-header">
                                            <i><?php echo $title; ?></i>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Node</th>
                                        <th>Node Information</th>
                                        <th>Received</th>
                                        <th>Link</th>
                                        <th>Dir</th>
                                        <th>Connected</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="initializing-row"> <?php // Class helps targeting if needed ?>
                                        <td colspan="7"><i>Initializing...</i></td> <?php // Removed inline style ?>
                                    </tr>
                                    <?php // Real data rows added by JS go here ?>
                                </tbody>
                            </table>
                        <?php
                        } else { // Compact View Table
                        ?>
                            <table class="gridtable-large node-table-compact" id="table_<?php echo htmlspecialchars($node); ?>"> <?php // Removed inline styles ?>
                                <colgroup> <col> <col> <col> <col> <col> </colgroup> <?php // Let CSS/content define widths ?>
                                <thead>
                                    <tr>
                                        <th colspan="5" class="table-title-header">
                                            <i><?php echo $title; ?></i>
                                        </th>
                                    </tr>
                                    <tr>
                                        <th>Node</th>
                                        <th>Node Information</th>
                                        <th>Link</th>
                                        <th>Dir</th>
                                        <th>Mode</th>
                                    </tr>
                                </thead>
                                 <tbody>
                                     <tr class="initializing-row"> <?php // Class helps targeting if needed ?>
                                         <td colspan="5"><i>Initializing...</i></td> <?php // Removed inline style ?>
                                     </tr>
                                     <?php // Real data rows added by JS go here ?>
                                </tbody>
                            </table>
                        <?php
                        } // End if Show_Detail
                        ?>
                    </div> <?php // End table-wrapper ?>
                </td>
            </tr>
            <?php
        } // End foreach node
        ?>
        </tbody>
    </table> <?php // End fxwidth table ?>
</div> <?php // End list_link div ?>

<?php // --- HamClock (Conditional) ---
if (isset($HAMCLOCK_ENABLED) && $HAMCLOCK_ENABLED == true && isset($HAMCLOCK_URL) && !empty($HAMCLOCK_URL)) {
?>
    <?php // Container div for responsive iframe styling ?>
    <div class="hamclock-container">
        <iframe title="HamClock" src="<?php echo htmlspecialchars($HAMCLOCK_URL); ?>"></iframe> <?php // Removed fixed width/height, use CSS ?>
    </div>
<?php
}
?>

<?php // --- Footer Area --- ?>
<div class="footer-area"> <?php // Class for styling ?>
    <?php
    if ($Show_Detail == 1) {
        // ID is fine for spinny if JS targets it
        echo "<div id=\"spinny\" style=\"display: inline-block; margin-right: 10px; font-weight: bold; width: 10px; text-align: center;\">*</div>";
    }

    // Login Status Message
    $ini_file_name = get_ini_name($_SESSION['user'] ?? '');
    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
    // Use TEXT_SIZE variable to add a class for potential font sizing via CSS
    $login_status_class = isset($TEXT_SIZE) && $TEXT_SIZE ? htmlspecialchars($TEXT_SIZE) : 'text-normal'; // Default class

    echo "<p class=\"login-status " . $login_status_class . "\">"; // Add base class
    if (empty($_SESSION['user'])) {
        echo "<i>Not logged in from IP: <b>" . htmlspecialchars($remote_addr) . "</b>";
        echo "   |   Using config: '<b>" . htmlspecialchars($ini_file_name) . "</b>'</i>";
    } else {
        echo "<i>Logged in as <b>" . htmlspecialchars($_SESSION["user"]) . "</b> from IP: <b>" . htmlspecialchars($remote_addr) . "</b>";
        echo "   |   Using config: '<b>" . htmlspecialchars($ini_file_name) . "</b>'</i>";
    }
    echo "</p>";

    // Include Footer
    if (file_exists("footer.inc")) {
        include "footer.inc";
    } else {
        // Use a class for error message styling
        echo "<p class='error-message'>Error: Required file 'footer.inc' not found.</p>";
    }
    ?>
</div> <?php // End Footer Area div ?>
