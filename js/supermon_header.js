// js/supermon_header.js

$(document).ready(function() {
    // Cache common selectors
    const $loginLink = $('#loginlink');
    const $logoutLink = $('#logoutlink');
    const $connectForm = $('#connect_form');
    const $loginDialog = $('#login'); // Assuming #login is the login dialog container

    // These inputs are central to many operations
    const $localnodeInput = $('#localnode');
    const $nodeInput = $('#node'); // Often used for remote node or command parameters
    const $permCheckbox = $('#perm_checkbox');

    // --- Helper Functions ---

    // Helper for opening windows (simple, no node params in URL but uses localnode for window name)
    function openWindowSimple(baseUrl, windowNamePrefix, windowSize) {
        return function(event) {
            event.preventDefault();
            const localnodeVal = $localnodeInput.val() || 'unknown';
            const url = baseUrl;
            const windowName = `${windowNamePrefix}${localnodeVal}`;
            const myWindow = window.open(url, windowName, windowSize);
            if (myWindow && typeof myWindow.moveTo === 'function') {
                myWindow.moveTo(20, 20);
            }
        };
    }

    // Helper for opening windows that need localnode as a URL parameter
    function openWindowWithLocalNode(baseUrl, windowNamePrefix, windowSize, paramName = 'node') {
        return function(event) {
            event.preventDefault();
            const localnodeVal = $localnodeInput.val();
            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            const url = `${baseUrl}?${paramName}=${encodeURIComponent(localnodeVal)}`;
            const windowName = `${windowNamePrefix}${localnodeVal}`;
            const myWindow = window.open(url, windowName, windowSize);
            if (myWindow && typeof myWindow.moveTo === 'function') {
                myWindow.moveTo(20, 20);
            }
        };
    }

    // Helper for opening windows that need 'node' (from $nodeInput) and 'localnode' as URL parameters
    function openWindowWithNodeAndLocalNode(baseUrl, windowNamePrefix, windowSize) {
        return function(event) {
            event.preventDefault();
            const nodeVal = $nodeInput.val();
            const localnodeVal = $localnodeInput.val();

            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            // Note: nodeVal (from $nodeInput) might be optional for some scripts, they should handle it.
            const url = `${baseUrl}?node=${encodeURIComponent(nodeVal)}&localnode=${encodeURIComponent(localnodeVal)}`;
            const windowName = `${windowNamePrefix}${localnodeVal}`;
            const myWindow = window.open(url, windowName, windowSize);
            if (myWindow && typeof myWindow.moveTo === 'function') {
                myWindow.moveTo(20, 20);
            }
        };
    }

    // Helper for server actions with confirmation
    function performServerAction(actionConfig) {
        // actionConfig: {
        //   phpFile: string,
        //   confirmMsg: string | function(localnode, node, buttonId),
        //   successMsgHandler: function(result, buttonId, localnode, node), (optional)
        //   failMsg: string,
        //   noActionMsg: string | function(localnode, node, buttonId),
        //   dataBuilder: function(buttonId, localnode, node),
        //   requiresLocalNode: boolean (default: true)
        // }
        return function() {
            const buttonId = this.id;
            const localnode = $localnodeInput.val();
            const node = $nodeInput.val(); // Value from the general 'node' input field

            if (actionConfig.requiresLocalNode !== false && !localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }

            const confirmMessage = (typeof actionConfig.confirmMsg === 'function') ?
                actionConfig.confirmMsg(localnode, node, buttonId) :
                actionConfig.confirmMsg;

            alertify.confirm(confirmMessage, (e) => {
                if (e) {
                    const ajaxData = actionConfig.dataBuilder(buttonId, localnode, node);
                    $.ajax({
                        url: actionConfig.phpFile,
                        data: ajaxData,
                        type: 'post',
                        success: function(result) {
                            if (actionConfig.successMsgHandler) {
                                actionConfig.successMsgHandler(result, buttonId, localnode, node);
                            } else {
                                alertify.success(result);
                            }
                        },
                        error: function(jqXHR, textStatus) {
                            alertify.error(`${actionConfig.failMsg}: ${textStatus}`);
                        }
                    });
                } else {
                    const noActionMessage = (typeof actionConfig.noActionMsg === 'function') ?
                        actionConfig.noActionMsg(localnode, node, buttonId) :
                        actionConfig.noActionMsg;
                    alertify.error(noActionMessage);
                }
            });
        };
    }

    // --- Initial UI state based on login status ---
    if (supermonConfig.isLoggedIn) {
        $loginLink.hide();
        if ($logoutLink.length) {
            // Ensure username is HTML-escaped if it could contain special characters
            const safeUsername = $('<div/>').text(supermonConfig.username).html();
            $logoutLink.show().find('span').append(` ${safeUsername}`);
        }
        $connectForm.show();
    } else {
        $connectForm.hide();
        $logoutLink.hide();
        $loginLink.show();
    }

    // --- Event Handlers for Logged-In Users ---
    if (supermonConfig.isLoggedIn) {

        $logoutLink.click(function(event) {
            event.preventDefault();
            const user = supermonConfig.username; // Assume username is safe or escape it if displayed raw
            alertify.success(`<p style="font-size:28px;"><b>Goodbye ${$('<div/>').text(user).html()}!</b></p>`);

            $.post("logout.php", "")
                .done(function(response) {
                    const responseText = (typeof response === 'string') ? response.trim() : '';
                    if (responseText && !responseText.toLowerCase().startsWith('sorry')) {
                        sleep(2000).then(() => {
                            window.location.reload();
                        });
                    } else {
                        alertify.error(responseText || "Logout failed. Please try again.");
                    }
                })
                .fail(function() {
                    alertify.error("Logout request failed. Server error.");
                });
        });

        $('#connect, #monitor, #permanent, #localmonitor').click(function() {
            const button = this.id;
            const localNode = $localnodeInput.val();
            const remoteNode = $nodeInput.val();
            const perm = $permCheckbox.is(':checked') ? $permCheckbox.val() : '';

            if (!remoteNode) {
                alertify.error(`Please enter the remote node number you want node ${localNode || 'your node'} to connect with.`);
                return;
            }
            if (!localNode) {
                alertify.error('Please select or ensure your local node is available.');
                return;
            }

            $.ajax({
                url: 'connect.php',
                data: { remotenode: remoteNode, perm: perm, button: button, localnode: localNode },
                type: 'post',
                success: function(result) { alertify.success(result); },
                error: function(jqXHR, textStatus) { alertify.error(`Connect request failed: ${textStatus}`); }
            });
        });

        $('#disconnect').click(function() {
            const button = this.id; // 'disconnect'
            const localNode = $localnodeInput.val();
            const remoteNode = $nodeInput.val();
            const perm = $permCheckbox.is(':checked') ? $permCheckbox.val() : '';

            if (!remoteNode) {
                alertify.error(`Please enter the remote node number you want node ${localNode || 'your node'} to disconnect from.`);
                return;
            }
            if (!localNode) {
                alertify.error('Please select or ensure your local node is available.');
                return;
            }

            alertify.confirm(`Disconnect ${remoteNode} from ${localNode}?`, function(e) {
                if (e) {
                    $.ajax({
                        url: 'disconnect.php',
                        data: { remotenode: remoteNode, perm: perm, button: button, localnode: localNode },
                        type: 'post',
                        success: function(result) { alertify.success(result); },
                        error: function(jqXHR, textStatus) { alertify.error(`Disconnect request failed: ${textStatus}`); }
                    });
                }
            });
        });

        $('#controlpanel').click(openWindowWithLocalNode("controlpanel.php", "ControlPanel", 'height=560, width=1000'));
        $('#favoritespanel').click(openWindowWithLocalNode("favorites.php", "FavoritesPanel", 'height=500, width=800'));
        $('#stats').click(openWindowWithLocalNode("stats.php", "AllStarStatistics", 'height=560, width=1400'));
        
        $('#database').click(openWindowWithNodeAndLocalNode("database.php", "Database", 'height=560, width=950'));
        $('#rptstats').click(openWindowWithNodeAndLocalNode("rptstats.php", "RptStatistics", 'height=800, width=900'));
        $('#openbanallow').click(openWindowWithNodeAndLocalNode("node-ban-allow-intro.php", "Ban-Allow", 'height=700, width=750'));
        $('#opensimpleusb').click(openWindowWithNodeAndLocalNode("simpleusb-control-intro.php", "Simpleusb-tune-menu ", 'height=700, width=1000'));

        $('#astlog').click(openWindowSimple("astlog.php", "AsteriskLog", 'height=560, width=1300'));
        $('#cpustats').click(openWindowSimple("cpustats.php", "CPUstatistics", 'height=760, width=1000'));
        $('#astnodes').click(openWindowSimple("astnodes.php", "AstNodes", 'height=560, width=750'));
        $('#extnodes').click(openWindowSimple("extnodes.php", "ExtNodes", 'height=560, width=850'));
        $('#linuxlog').click(openWindowSimple("linuxlog.php", "LinuxLog", 'height=560, width=1300'));
        $('#irlplog').click(openWindowSimple("irlplog.php", "IRLPLog", 'height=560, width=1100'));
        $('#webacclog').click(openWindowSimple("webacclog.php", "WebAccessLog", 'height=560, width=1400'));
        $('#weberrlog').click(openWindowSimple("weberrlog.php", "WebErrorLog", 'height=560, width=1400'));
        $('#openpigpio').click(openWindowSimple("pi-gpio.php", "Pi-GPIO", 'height=900, width=900'));
        $('#smlog').click(openWindowSimple("smlog.php", "SMLog", 'height=560, width=1200'));

        $('#astlookup').click(function(event) {
            event.preventDefault();
            const nodeVal = $nodeInput.val();
            const localnodeVal = $localnodeInput.val();
            const perm = $permCheckbox.is(':checked') ? $permCheckbox.val() : '';

            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            if (!nodeVal) {
                alertify.error(`Please enter a Callsign or Node number to look up on node ${localnodeVal}.`);
                return;
            }
            const url = `astlookup.php?node=${encodeURIComponent(nodeVal)}&localnode=${encodeURIComponent(localnodeVal)}&perm=${encodeURIComponent(perm)}`;
            const windowName = `AstLookup${localnodeVal}`;
            const windowSize = 'height=500,width=1000';
            const myWindow = window.open(url, windowName, windowSize);
            if (myWindow && typeof myWindow.moveTo === 'function') {
                myWindow.moveTo(20, 20);
            }
        });

        const genericActionConfig = {
            failMsgBase: "request failed",
            noActionMsgBase: "No action performed",
            dataBuilder: (buttonId, localnode, node) => ({ button: buttonId, localnode: localnode, node: node }) // 'node' is from $nodeInput
        };
        
        $('#astreload').click(performServerAction({
            ...genericActionConfig,
            phpFile: 'ast_reload.php',
            confirmMsg: (localnode) => `Execute the Asterisk "iax2, rpt, & extensions Reload" for node - ${localnode || 'N/A'}?`,
            failMsg: "Reload request failed",
            noActionMsg: "No reload performed.",
            requiresLocalNode: true
        }));

        $('#reboot').click(performServerAction({
            ...genericActionConfig,
            phpFile: 'reboot.php',
            confirmMsg: "Perform a full Reboot of the AllStar server?<br><br>You can only Reboot the main server from Supermon, not remote servers.",
            failMsg: "Reboot request failed",
            noActionMsg: "Reboot not performed.",
            requiresLocalNode: false // Reboot may not require a specific local node selected
        }));
        
        const astarOnOffActionConfig = {
            phpFile: 'astaronoff.php',
            confirmMsg: (localnode, node, buttonId) => (buttonId === 'astaroff') ?
                "Perform Shutdown of AllStar system software?" :
                "Perform Startup of AllStar system software?",
            failMsg: "Asterisk control request failed",
            noActionMsg: (localnode, node, buttonId) => (buttonId === 'astaroff') ? "Shutdown not performed." : "Startup not performed.",
            dataBuilder: (buttonId, localnode) => ({ 'button': buttonId, 'localnode': localnode }),
            requiresLocalNode: true // Assuming localnode is needed by astaronoff.php
        };
        $('#astaron').click(performServerAction(astarOnOffActionConfig));
        $('#astaroff').click(performServerAction(astarOnOffActionConfig));

        $('#fastrestart').click(performServerAction({
            phpFile: 'fastrestart.php',
            confirmMsg: (localnode) => `Perform a Fast-Restart of the AllStar system software at node ${localnode || 'N/A'}?`,
            failMsg: "Fast Restart request failed",
            noActionMsg: "Fast Restart not performed.",
            dataBuilder: (buttonId, localnode) => ({ 'button': buttonId, 'localnode': localnode }),
            requiresLocalNode: true
        }));
        
        $('#dtmf').click(function() {
            const button = this.id; // 'dtmf'
            const dtmfCommand = $nodeInput.val(); // DTMF command is entered in the #node field
            const localnode = $localnodeInput.val();

            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            if (!dtmfCommand) {
                alertify.error(`Please enter a DTMF command to execute on node ${localnode}.`);
                return;
            }

            $.ajax({
                url: 'dtmf.php',
                data: { node: dtmfCommand, button: button, localnode: localnode },
                type: 'post',
                success: function(result) { alertify.success(result); },
                error: function(jqXHR, textStatus) { alertify.error(`DTMF request failed: ${textStatus}`); }
            });
        });

        $('#map').click(function() {
            const button = this.id;
            const nodeParam = $nodeInput.val(); // Value from general #node input, its purpose for map is specific to bubblechart.php
            const localnode = $localnodeInput.val();

            if (!localnode) {
                alertify.error("Local node must be selected or available to view the map.");
                return;
            }

            $.ajax({
                url: 'bubblechart.php',
                data: { node: nodeParam, localnode: localnode, button: button },
                type: 'post',
                success: function(result) {
                    const $testArea = $('#test_area');
                    if ($testArea.length) {
                        $testArea.html(result).stop(true, true).css('opacity', 1).fadeIn(50).delay(1000).fadeOut(2000);
                    } else {
                        console.warn("Target element #test_area not found for map result.");
                        const safeResult = $('<div/>').text(result).html(); // Escape result for safe display
                        alertify.alert("Map Data (target #test_area missing)", `<pre>${safeResult}</pre>`);
                    }
                },
                error: function(jqXHR, textStatus) { alertify.error(`Map request failed: ${textStatus}`); }
            });
        });

    } // --- End of Event Handlers for Logged-In Users ---


    // --- Event Handlers Available Regardless of Login State ---

    // Click on table cell to populate connection form's main #node and #localnode inputs
    $('table').on('click', 'td.nodeNum', function() {
        if (!$connectForm.is(':visible')) {
            return; // Don't populate if form isn't visible (i.e., user not logged in)
        }

        const clickedNodeNum = $(this).text().trim();
        $nodeInput.val(clickedNodeNum); // Populates the main #node input

        const tableId = $(this).closest('table').attr('id');
        if (tableId) {
            const idParts = tableId.split('_');
            if (idParts.length > 1 && idParts[1]) {
                $localnodeInput.val(idParts[1]); // Populates the main #localnode input
            }
        }
    });

    // Show login dialog
    $loginLink.click(function(event) {
        event.preventDefault();
        clearForm();
        showLogin();
    });

}); // --- End of $(document).ready ---


// --- Login Form Helper Functions (can be outside document.ready if they don't rely on its scoped vars) ---

function clearForm() {
    // Assuming login form has id "myform" and specific inputs "checkbox", "passwd"
    const loginForm = document.getElementById("myform");
    if (loginForm) {
        loginForm.reset(); // Standard way to clear a form to its initial state
        // If you need to truly blank fields rather than reset to initial values:
        // $('#myform :input').not(':button, :submit, :reset, :hidden, :radio').val('');
        // $('#myform input[type="checkbox"]').prop('checked', false);
    }
    const passwdField = document.getElementById("passwd");
    if (passwdField) {
        passwdField.type = "password"; // Ensure password field is masked
    }
    const checkbox = document.getElementById("checkbox"); // Show/Hide PW checkbox
    if (checkbox) {
        checkbox.checked = false;
    }
}

function showPW() {
    const passwdField = document.getElementById("passwd");
    const userField = document.getElementById("user");
    const checkbox = document.getElementById("checkbox");

    if (!userField || !passwdField || !checkbox) {
        console.warn("showPW: One or more required elements (user, passwd, checkbox) not found.");
        return;
    }

    if (userField.value.trim()) { // Only toggle if username is entered
        passwdField.type = (passwdField.type === "password") ? "text" : "password";
        checkbox.checked = (passwdField.type === "text");
    } else {
        passwdField.type = "password";
        checkbox.checked = false;
    }
}

function hideLogin() {
    $('#login').hide(); // Or use cached $loginDialog if defined globally or passed
}

function showLogin() {
    $('#login').show(); // Or use cached $loginDialog
    const $userField = $('#login #user'); // Login dialog specific user field
    if ($userField.length) {
        $userField.focus();
    }
}

function validate_login() {
    const userVal = $("#user").val().trim(); // User input from login form
    const passwdVal = $("#passwd").val();    // Password - usually not trimmed by client

    if (!userVal || !passwdVal.trim()) { // Check if password is not just spaces
        alertify.error("Username and Password are required.");
        return false;
    }

    $.ajax({
        type: "POST",
        url: "login.php",
        data: { user: userVal, passwd: passwdVal }, // Send original password
        dataType: 'text',
        success: function(response) {
            hideLogin();
            const responseText = (typeof response === 'string') ? response.trim() : '';
            if (responseText && !responseText.toLowerCase().startsWith('sorry')) {
                const safeUser = $('<div/>').text(userVal).html();
                alertify.success(`<p style="font-size:28px;"><b>Welcome ${safeUser}!</b></p>`);
                sleep(2000).then(() => {
                    window.location.reload();
                });
            } else {
                alertify.error(responseText || "Sorry, Login Failed!");
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideLogin();
            let errorMsg = `Login request failed: ${textStatus}`;
            if (errorThrown) errorMsg += ` - ${errorThrown}`;
            alertify.error(errorMsg);
        }
    });
    return false; // Prevent default form submission if this is an onsubmit handler
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}