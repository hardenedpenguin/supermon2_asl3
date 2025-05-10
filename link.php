<?php
global $nodes, $config, $astdb, $Show_Detail, $SUBMITTER, $SUBMIT_SIZE, $TEXT_SIZE;
global $WELCOME_MSG, $WELCOME_MSG_LOGGED;
global $system_type, $EXTN, $IRLPLOG, $DATABASE_TXT;
global $HAMCLOCK_ENABLED, $HAMCLOCK_URL;
global $user;
global $DVM_URL;

function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

$welcome_output = '';
$is_logged_in = $_SESSION['sm61loggedin'] ?? false;

if (!$is_logged_in && isset($WELCOME_MSG) && !empty(trim($WELCOME_MSG))) {
    $welcome_output = "<div class='welcome-message not-logged-in'>" . $WELCOME_MSG . "</div>\n";
} elseif ($is_logged_in && isset($WELCOME_MSG_LOGGED) && !empty(trim($WELCOME_MSG_LOGGED))) {
    $welcome_output = "<div class='welcome-message logged-in'>" . $WELCOME_MSG_LOGGED . "</div>\n";
}
echo $welcome_output;

if ($is_logged_in === true) {
?>
    <div id="connect_form">
        <?php if (count($nodes) > 0) : ?>
            <div>
                <?php
                if (count($nodes) > 1) {
                    echo "<select id=\"localnode\" class=\"" . h($SUBMIT_SIZE) . "\">";
                    foreach ($nodes as $node) {
                        $node_val = h($node);
                        $node_display = $node_val;
                        $info_text = "Node not in database";
                        if (isset($astdb[$node][1])) {
                            $info_parts = array_filter(array_slice($astdb[$node], 1, 3));
                            $info_text = (!empty($info_parts) ? h(implode(' ', $info_parts)) : '');
                        } elseif (isset($config[$node]['nvNodeInfo'])) {
                            $info_text = h($config[$node]['nvNodeInfo']);
                        }
                        if ($info_text !== "Node not in database" && !empty($info_text) && $info_text !== h("Node not in database")) {
                             $node_display .= " (" . $info_text . ")";
                        }
                        echo "<option class=\"" . h($SUBMIT_SIZE) . "\" value=\"" . $node_val . "\"> " . $node_display . " </option>";
                    }
                    echo "</select>";
                } else {
                    echo "<input type=\"hidden\" id=\"localnode\" value=\"" . h($nodes[0]) . "\">\n";
                }

                $input_style_val = ($Show_Detail != 1) ? "font-size:22px; width: 150px;" : "font-size:16px; width: 120px;";
                $input_style_val .= " vertical-align: middle; margin: 5px;";
                $label_style_val = ($Show_Detail != 1) ? "font-size:22px;" : "font-size:16px;";
                $label_style_val .= " vertical-align: middle; color: white; margin: 5px;";

                echo "<input style=\"" . h($input_style_val) . "\" type=\"text\" id=\"node\" placeholder=\"\" autocomplete=\"off\">";

                if (get_user_auth("PERMUSER")) {
                    echo "<label style=\"" . h($label_style_val) . "\"> Perm <input class=\"perm\" type=\"checkbox\" style=\"vertical-align: middle;\"> </label>";
                }
                ?>
            </div>

            <div class="connect-form-buttons">
                <?php
                $button_common_style = "margin: 3px 2px;";
                $buttons = [
                    ["auth" => "CONNECTUSER", "class" => $SUBMIT_SIZE, "value" => "Connect", "id" => "connect"],
                    ["auth" => "DISCUSER",    "class" => $SUBMIT_SIZE, "value" => "Disconnect", "id" => "disconnect"],
                    ["auth" => "MONUSER",     "class" => $SUBMIT_SIZE, "value" => "Monitor", "id" => "monitor"],
                    ["auth" => "LMONUSER",    "class" => $SUBMIT_SIZE, "value" => "Local Monitor", "id" => "localmonitor"],
                    ["auth" => "DTMFUSER",    "class" => $SUBMITTER,   "value" => "DTMF", "id" => "dtmf"],
                    ["auth" => "ASTLKUSER",   "class" => $SUBMIT_SIZE, "value" => "Lookup", "id" => "astlookup"],
                    ["auth" => "RSTATUSER",   "class" => "submit",      "value" => "Rpt Stats", "id" => "rptstats"],
                    ["auth" => "BUBLUSER",    "class" => "submit",      "value" => "Bubble", "id" => "map"],
                    ["auth" => "CTRLUSER",    "class" => $SUBMITTER,   "value" => "Control", "id" => "controlpanel"],
                    ["auth" => "FAVUSER",     "class" => $SUBMIT_SIZE, "value" => "Favorites", "id" => "favoritespanel"],
                ];

                foreach ($buttons as $btn) {
                    if (get_user_auth($btn["auth"])) {
                        echo "<input style=\"" . h($button_common_style) . "\" type=\"button\" class=\"" . h($btn["class"]) . "\" value=\"" . h($btn["value"]) . "\" id=\"" . h($btn["id"]) . "\">";
                    }
                }

                if ($Show_Detail == 1) {
                    echo "<hr class='button-separator' style='border: none; height: 1px; background-color: #555; margin: 10px 0;'>";
                    $detail_buttons = [
                        ["auth" => "CFGEDUSER",  "class" => $SUBMITTER, "value" => "Configuration Editor", "onclick" => "window.open('configeditor.php');"],
                        ["auth" => "ASTRELUSER", "class" => $SUBMITTER, "value" => "Iax/Rpt/DP RELOAD", "id" => "astreload"],
                        ["auth" => "ASTSTRUSER", "class" => $SUBMITTER, "value" => "AST START", "id" => "astaron"],
                        ["auth" => "ASTSTPUSER", "class" => $SUBMITTER, "value" => "AST STOP", "id" => "astaroff"],
                        ["auth" => "FSTRESUSER", "class" => $SUBMITTER, "value" => "RESTART", "id" => "fastrestart"],
                        ["auth" => "RBTUSER",    "class" => $SUBMITTER, "value" => "Server REBOOT", "id" => "reboot"],
                        ["auth" => "HWTOUSER",   "class" => "submit",    "value" => "AllStar How To's", "onclick" => "OpenHelp()"],
                        ["auth" => "WIKIUSER",   "class" => "submit",    "value" => "AllStar Wiki", "onclick" => "OpenWiki()"],
                        ["auth" => "CSTATUSER",  "class" => "submit",    "value" => "CPU Status", "id" => "cpustats"],
                        ["auth" => "ASTATUSER",  "class" => "submit",    "value" => "AllStar Status", "id" => "stats"],
                        ["auth" => "EXNUSER",    "condition" => isset($EXTN) && $EXTN, "class" => "submit", "value" => "Registry", "id" => "extnodes"],
                        ["auth" => "NINFUSER",   "class" => "submit",    "value" => "Node Info", "id" => "astnodes"],
                        ["auth" => "ACTNUSER",   "class" => "submit",    "value" => "Active Nodes", "onclick" => "OpenActiveNodes()"],
                        ["auth" => "ALLNUSER",   "class" => "submit",    "value" => "All Nodes", "onclick" => "OpenAllNodes()"],
                        ["auth" => "LLOGUSER",   "class" => "submit",    "value" => "Linux Log", "id" => "linuxlog"],
                        ["auth" => "ASTLUSER",   "class" => "submit",    "value" => "AST Log", "id" => "astlog"],
                        ["auth" => "WLOGUSER",   "class" => "submit",    "value" => "Web Access Log", "id" => "webacclog"],
                        ["auth" => "WERRUSER",   "class" => "submit",    "value" => "Web Error Log", "id" => "weberrlog"],
                        ["auth" => "BANUSER",    "class" => $SUBMIT_SIZE, "value" => "Restrict", "id" => "openbanallow"],
                        ["auth" => "DBTUSER",    "condition" => isset($DATABASE_TXT) && !empty($DATABASE_TXT), "class" => "submit", "value" => "Database", "id" => "database"],
                        ["auth" => "GPIOUSER",   "class" => $SUBMITTER, "value" => "GPIO", "id" => "openpigpio"],
                        ["auth" => "IRLPLOGUSER","condition" => isset($IRLPLOG) && $IRLPLOG, "class" => "submit", "value" => "IRLP Log", "id" => "irlplog"],
                    ];

                    foreach ($detail_buttons as $btn) {
                        if (get_user_auth($btn["auth"]) && (!isset($btn["condition"]) || $btn["condition"])) {
                            $attrs = "style=\"" . h($button_common_style) . "\" type=\"button\" class=\"" . h($btn["class"]) . "\" value=\"" . h($btn["value"]) . "\"";
                            if (isset($btn["id"])) $attrs .= " id=\"" . h($btn["id"]) . "\"";
                            if (isset($btn["onclick"])) $attrs .= " onclick=\"" . h($btn["onclick"]) . "\"";
                            echo "<input " . $attrs . ">";
                        }
                    }
                }
                ?>
            </div>
            <script>
                function OpenActiveNodes()  { window.open('http://stats.allstarlink.org'); }
                function OpenAllNodes()     { window.open('https://www.allstarlink.org/nodelist'); }
                function OpenHelp()         { window.open('https://wiki.allstarlink.org/wiki/Category:How_to'); }
                function OpenConfigEditor() { window.open('configeditor.php'); }
                function OpenWiki()         { window.open('http://wiki.allstarlink.org'); }
            </script>
        <?php else : ?>
            <p style='color: white;'>No nodes configured or passed in the URL.</p>
        <?php endif; ?>
    </div>
<?php
}
?>
<div class="config-info-buttons" style="text-align: center; margin-bottom: 15px;">
    <input type="button" class="<?php echo h($SUBMIT_SIZE); ?>" Value="Display Configuration" onclick="window.open('display-config.php','DisplayConfiguration','status=no,location=no,toolbar=no,width=500,height=600,left=100,top=100')">
    <?php if (isset($DVM_URL) && !empty(trim($DVM_URL))) : ?>
        <input type="button" class="<?php echo h($SUBMIT_SIZE); ?>" Value="Digital Dashboard" onclick="window.open('<?php echo h($DVM_URL); ?>','DigitalConfiguration','status=no,location=no,toolbar=no,width=940,height=890,left=10,top=10')">
    <?php endif; ?>
    <?php if ($is_logged_in === true && get_user_auth("SYSINFUSER")) :
        $width  = ($Show_Detail == 1) ? 950 : 650;
        $height = ($Show_Detail == 1) ? 550 : 750;
    ?>
        <input type="button" class="<?php echo h($SUBMITTER); ?>" Value="System Info" onclick="window.open('system-info.php','SystemInfo','status=no,location=no,toolbar=yes,width=<?php echo $width; ?>,height=<?php echo $height; ?>,left=100,top=100')">
    <?php endif; ?>
</div>

<div id="list_link">
    <table class="fxwidth">
        <tbody>
        <?php foreach ($nodes as $node_key) :
            $node_h = h($node_key);
            $info = "Node not in database";
            if (isset($astdb[$node_key][1])) {
                $info_parts = array_filter(array_slice($astdb[$node_key], 1, 3));
                $info = !empty($info_parts) ? h(implode(' ', $info_parts)) : '';
            } elseif (isset($config[$node_key]['nvNodeInfo'])) {
                $info = h($config[$node_key]['nvNodeInfo']);
            }

            $nodeURL = ""; $bubbleChart = ""; $lsNodesChart = ""; $listenLiveLink = ""; $archiveLink = ""; $customNodeLink = ""; $customNodeLinkTarget = "_self";
            $custom_url_var = 'URL_' . $node_key;
            if (isset(${$custom_url_var}) && !empty(${$custom_url_var})) {
                $customNodeLink = ${$custom_url_var};
                if (substr($customNodeLink, -1) == ">") {
                    $customNodeLink = substr($customNodeLink, 0, -1);
                    $customNodeLinkTarget = "_blank";
                }
            }
            $is_private = (isset($config[$node_key]['hideNodeURL']) && $config[$node_key]['hideNodeURL'] == 1);
            if (!$is_private && intval($node_key) >= 2000) {
                $nodeURL = "http://stats.allstarlink.org/nodeinfo.cgi?node=" . urlencode($node_key);
                $bubbleChart = "http://stats.allstarlink.org/getstatus.cgi?" . urlencode($node_key);
            }
            if (isset($config[$node_key]['lsnodes'])) $lsNodesChart = $config[$node_key]['lsnodes'];
            elseif (isset($config[$node_key]['host']) && (preg_match("/localhost/", $config[$node_key]['host']) || preg_match("/127\.0\.0\.1/", $config[$node_key]['host']))) {
                $lsNodesChart = "/cgi-bin/lsnodes_web?node=" . urlencode($node_key);
            }
            if (isset($config[$node_key]['listenlive'])) $listenLiveLink = $config[$node_key]['listenlive'];
            if (isset($config[$node_key]['archive']))    $archiveLink    = $config[$node_key]['archive'];

            $title_text = ($is_private) ? "Private Node " : "Node ";
            if (!empty($nodeURL)) {
                $title_text .= "<a href=\"" . h($nodeURL) . "\" target=\"_blank\">" . $node_h . "</a>";
            } elseif (!empty($customNodeLink) && $customNodeLinkTarget == '_self') {
                $title_text .= "<a href=\"" . h($customNodeLink) . "\">" . $node_h . "</a>";
            } else {
                $title_text .= $node_h;
            }
            $title_text .= " => ";
            if (!empty($customNodeLink)) {
                $title_text .= "<a href=\"" . h($customNodeLink) . "\" target=\"" . h($customNodeLinkTarget) . "\">" . $info . "</a>";
            } else {
                $title_text .= $info;
            }
            $title_text .= "  ";

            $links_line_arr = [];
            if (!empty($bubbleChart))    $links_line_arr[] = "<a href=\"" . h($bubbleChart) . "\" target=\"_blank\" id=\"bubblechart_" . $node_h . "\">Bubble Chart</a>";
            if (!empty($lsNodesChart))   $links_line_arr[] = "<a href=\"" . h($lsNodesChart) . "\" target=\"_blank\" id=\"lsnodeschart_" . $node_h . "\">lsNodes</a>";
            if (!empty($listenLiveLink)) $links_line_arr[] = "<a href=\"" . h($listenLiveLink) . "\" target=\"_blank\" id=\"listenlive_" . $node_h . "\">Listen Live</a>";
            if (!empty($archiveLink))    $links_line_arr[] = "<a href=\"" . h($archiveLink) . "\" target=\"_blank\" id=\"archive_" . $node_h . "\">Archive</a>";

            if (!empty($links_line_arr)) {
                $title_text .= "<br><span class='table-title-sublinks'>" . implode("    ", $links_line_arr) . "</span>";
            }
        ?>
            <tr>
                <td class="node-table-container-cell" style="padding-bottom: 20px; border: none;">
                    <div class="table-wrapper">
                        <?php if ($Show_Detail == 1) : ?>
                            <table class="gridtable node-table-detailed" id="table_<?php echo $node_h; ?>">
                                <colgroup><col><col><col><col><col><col><col></colgroup>
                                <thead>
                                    <tr><th colspan="7" class="table-title-header"><i><?php echo $title_text; ?></i></th></tr>
                                    <tr><th>Node</th><th>Node Information</th><th>Received</th><th>Link</th><th>Dir</th><th>Connected</th><th>Mode</th></tr>
                                </thead>
                                <tbody><tr class="initializing-row"><td colspan="7"><i>Initializing...</i></td></tr></tbody>
                            </table>
                        <?php else : ?>
                            <table class="gridtable-large node-table-compact" id="table_<?php echo $node_h; ?>">
                                <colgroup><col><col><col><col><col></colgroup>
                                <thead>
                                    <tr><th colspan="5" class="table-title-header"><i><?php echo $title_text; ?></i></th></tr>
                                    <tr><th>Node</th><th>Node Information</th><th>Link</th><th>Dir</th><th>Mode</th></tr>
                                </thead>
                                 <tbody><tr class="initializing-row"><td colspan="5"><i>Initializing...</i></td></tr></tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (isset($HAMCLOCK_ENABLED) && filter_var($HAMCLOCK_ENABLED, FILTER_VALIDATE_BOOLEAN) && isset($HAMCLOCK_URL) && !empty(trim($HAMCLOCK_URL))) : ?>
    <div style="text-align:center; margin-bottom: 10px;">
        <iframe src="<?php echo h($HAMCLOCK_URL); ?>" width="800" height="480" style="border:none;"></iframe>
    </div>
<?php endif; ?>

<div class="footer-area">
    <?php
    if ($Show_Detail == 1) {
        echo "<div id=\"spinny\" style=\"display: inline-block; margin-right: 10px; font-weight: bold; width: 10px; text-align: center;\">*</div>";
    }

    $ini_file_name = h(get_ini_name($_SESSION['user'] ?? ''));
    $remote_addr = h($_SERVER['REMOTE_ADDR'] ?? 'Unknown IP');
    $login_status_class = (isset($TEXT_SIZE) && $TEXT_SIZE) ? h($TEXT_SIZE) : 'text-normal';

    echo "<p class=\"login-status " . $login_status_class . "\">";
    if (empty($_SESSION['user'])) {
        echo "<i>Not logged in from IP: <b>" . $remote_addr . "</b>";
        echo "   |   Using config: '<b>" . $ini_file_name . "</b>'</i>";
    } else {
        echo "<i>Logged in as <b>" . h($_SESSION["user"]) . "</b> from IP: <b>" . $remote_addr . "</b>";
        echo "   |   Using config: '<b>" . $ini_file_name . "</b>'</i>";
    }
    echo "</p>";

    if (file_exists("footer.inc")) {
        include "footer.inc";
    } else {
        echo "<p class='error-message'>Error: Required file 'footer.inc' not found.</p>";
    }
    ?>
</div>