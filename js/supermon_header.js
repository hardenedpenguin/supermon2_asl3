// js/supermon_header.js

$(document).ready(function() {

    var hideLoginLink = false;
    if (hideLoginLink) {
        $('#loginlink').hide();
    }

    // Initial UI state based on login status
    if (supermonConfig.isLoggedIn) {
        $('#loginlink').hide();
        if ($('#logoutlink').length) {
            $('#logoutlink').show().find('span').append(' ' + supermonConfig.username);
        }
        $('#connect_form').show();
    } else {
        $('#connect_form').hide();
        $('#logoutlink').hide();
        $('#loginlink').show();
    }

    // --- Event Handlers for Logged-In Users ---
    if (supermonConfig.isLoggedIn) {

        $('#logoutlink').click(function(event) {
            event.preventDefault();
            var user = supermonConfig.username;
            alertify.success("<p style=\"font-size:28px;\"><b>Goodbye " + user + "!</b></p>");

            $.post("logout.php", "", function(response) {
                if (response.substr(0, 5) != 'Sorry') {
                    sleep(2000).then(() => {
                        window.location.reload();
                    });
                } else {
                    alertify.error("Logout failed. Please try again.");
                }
            }).fail(function() {
                alertify.error("Logout request failed. Server error.");
            });
        });

        $('#connect, #monitor, #permanent, #localmonitor').click(function() {
            var button = this.id;
            var localNode = $('#localnode').val();
            var remoteNode = $('#node').val();
            var perm = $('#perm_checkbox:checked').length ? $('#perm_checkbox:checked').val() : '';

            if (!remoteNode || remoteNode.length === 0) {
                alertify.error('Please enter the remote node number you want node ' + localNode + ' to connect with.');
                return;
            }
            if (!localNode) {
                alertify.error('Please select or ensure your local node is available.');
                return;
            }

            $.ajax({
                url: 'connect.php',
                data: {
                    'remotenode': remoteNode,
                    'perm': perm,
                    'button': button,
                    'localnode': localNode
                },
                type: 'post',
                success: function(result) {
                    alertify.success(result);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alertify.error("Connect request failed: " + textStatus);
                }
            });
        });

        $('#disconnect').click(function() {
            var button = this.id;
            var localNode = $('#localnode').val();
            var remoteNode = $('#node').val();
            var perm = $('#perm_checkbox:checked').length ? $('#perm_checkbox:checked').val() : '';

            if (!remoteNode || remoteNode.length === 0) {
                alertify.error('Please enter the remote node number you want node ' + localNode + ' to disconnect from.');
                return;
            }
            if (!localNode) {
                alertify.error('Please select or ensure your local node is available.');
                return;
            }

            alertify.confirm("Disconnect " + remoteNode + " from " + localNode + "?",
                function(e) {
                    if (e) {
                        $.ajax({
                            url: 'disconnect.php',
                            data: {
                                'remotenode': remoteNode,
                                'perm': perm,
                                'button': button,
                                'localnode': localNode
                            },
                            type: 'post',
                            success: function(result) {
                                alertify.success(result);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alertify.error("Disconnect request failed: " + textStatus);
                            }
                        });
                    } else {
                        return;
                    }
                });
        });

        $('#controlpanel').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val();
            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "controlpanel.php?node=" + localnodeVal;
            var windowName = "ControlPanel" + localnodeVal;
            var windowSize = 'height=560, width=1000';
            window.open(url, windowName, windowSize);
        });

        $('#favoritespanel').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val();
            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "favorites.php?node=" + localnodeVal;
            var windowName = "FavoritesPanel" + localnodeVal;
            var windowSize = 'height=500, width=800';
            window.open(url, windowName, windowSize);
        });

        $('#astlog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "astlog.php";
            var windowName = "AsteriskLog" + localnodeVal;
            var windowSize = 'height=560, width=1300';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#stats').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val();
            if (!localnodeVal) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "stats.php?node=" + localnodeVal;
            var windowName = "AllStarStatistics" + localnodeVal;
            var windowSize = 'height=560, width=1400';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#astreload').click(function() {
            var button = this.id;
            var node = $('#node').val();
            var localnode = $('#localnode').val();

            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }

            alertify.confirm("Execute the Asterisk \"iax2, rpt, & extensions Reload\" for node - " + localnode,
                function(e) {
                    if (e) {
                        $.ajax({
                            url: 'ast_reload.php',
                            data: {
                                'node': node,
                                'localnode': localnode,
                                'button': button
                            },
                            type: 'post',
                            success: function(result) {
                                alertify.success(result);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alertify.error("Reload request failed: " + textStatus);
                            }
                        });
                    } else {
                        alertify.error("No reload performed");
                    }
                });
        });

        $('#reboot').click(function() {
            var button = this.id;
            var node = $('#node').val();
            var localnode = $('#localnode').val();

            alertify.confirm("Perform a full Reboot of the AllStar server?<br><br>You can only Reboot the main server from Supermon, not remote servers.",
                function(e) {
                    if (e) {
                        $.ajax({
                            url: 'reboot.php',
                            data: {
                                'node': node,
                                'localnode': localnode,
                                'button': button
                            },
                            type: 'post',
                            success: function(result) {
                                alertify.success(result);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alertify.error("Reboot request failed: " + textStatus);
                            }
                        });
                    } else {
                        alertify.error("NO Reboot performed");
                    }
                });
        });

        $('#cpustats').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "cpustats.php";
            var windowName = "CPUstatistics" + localnodeVal;
            var windowSize = 'height=760, width=1000';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#database').click(function(event) {
            event.preventDefault();
            var node = $('#node').val();
            var localnode = $('#localnode').val();
            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "database.php?node=" + node + "&localnode=" + localnode;
            var windowName = "Database" + localnode;
            var windowSize = 'height=560, width=950';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#astaron, #astaroff').click(function() {
            var button = this.id;
            var localnode = $('#localnode').val();
            var confirmMsg = (button === 'astaroff') ?
                "Perform Shutdown of AllStar system software?" :
                "Perform Startup of AllStar system software?";
            var errorMsg = (button === 'astaroff') ?
                "NO Action performed" :
                "NO action performed";

            alertify.confirm(confirmMsg,
                function(e) {
                    if (e) {
                        $.ajax({
                            url: 'astaronoff.php',
                            data: {
                                'button': button,
                                'localnode': localnode
                            },
                            type: 'post',
                            success: function(result) {
                                alertify.success(result);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alertify.error("Asterisk control request failed: " + textStatus);
                            }
                        });
                    } else {
                        alertify.error(errorMsg);
                    }
                });
        });

        $('#dtmf').click(function() {
            var button = this.id;
            var node = $('#node').val();
            var localnode = $('#localnode').val();

            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            if (!node || node.length === 0) {
                alertify.error("Please enter a DTMF command to execute on node " + localnode + '.');
                return;
            }

            $.ajax({
                url: 'dtmf.php',
                data: {
                    'node': node,
                    'button': button,
                    'localnode': localnode
                },
                type: 'post',
                success: function(result) {
                    alertify.success(result);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alertify.error("DTMF request failed: " + textStatus);
                }
            });
        });

        $('#rptstats').click(function(event) {
            event.preventDefault();
            var node = $('#node').val();
            var localnode = $('#localnode').val();
            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "rptstats.php?node=" + node + "&localnode=" + localnode;
            var windowName = "RptStatistics" + localnode;
            var windowSize = 'height=800, width=900';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#fastrestart').click(function() {
            var button = this.id;
            var localnode = $('#localnode').val();
            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }

            alertify.confirm("Perform a Fast-Restart of the AllStar system software at node " + localnode + "?",
                function(e) {
                    if (e) {
                        $.ajax({
                            url: 'fastrestart.php',
                            data: {
                                'button': button,
                                'localnode': localnode
                            },
                            type: 'post',
                            success: function(result) {
                                alertify.success(result);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alertify.error("Fast Restart request failed: " + textStatus);
                            }
                        });
                    } else {
                        alertify.error("NO action performed");
                    }
                });
        });

        $('#astlookup').click(function(event) {
            event.preventDefault();
            var button = this.id;
            var node = $('#node').val();
            var perm = $('#perm_checkbox:checked').length ? $('#perm_checkbox:checked').val() : '';
            var localnode = $('#localnode').val();

            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            if (!node || node.length === 0) {
                alertify.error('Please enter a Callsign or Node number to look up on node ' + localnode + '.');
                return;
            }
            var url = "astlookup.php?node=" + node + "&localnode=" + localnode + "&perm=" + perm;
            var windowName = "AstLookup" + localnode;
            var windowSize = 'height=500,width=1000';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#map').click(function() {
            var button = this.id;
            var node = $('#node').val();
            var localnode = $('#localnode').val();

            if (!localnode) {
                alertify.error("Local node must be selected or available to view the map.");
                return;
            }

            $.ajax({
                url: 'bubblechart.php',
                data: {
                    'node': node,
                    'localnode': localnode,
                    'button': button
                },
                type: 'post',
                success: function(result) {
                    const testArea = $('#test_area');
                    if (testArea.length) {
                        testArea.html(result);
                        testArea.stop()
                                .css('opacity', 1)
                                .fadeIn(50)
                                .delay(1000)
                                .fadeOut(2000);
                    } else {
                        console.warn("Target element #test_area not found for map result.");
                        alertify.alert("Map Data (target #test_area missing)", "<pre>" + String(result) + "</pre>", null);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alertify.error("Map request failed: " + textStatus);
                }
            });
        });

        $('#astnodes').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "astnodes.php";
            var windowName = "AstNodes" + localnodeVal;
            var windowSize = 'height=560, width=750';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#extnodes').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "extnodes.php";
            var windowName = "ExtNodes" + localnodeVal;
            var windowSize = 'height=560, width=850';
            window.open(url, windowName, windowSize);
        });

        $('#linuxlog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "linuxlog.php";
            var windowName = "LinuxLog" + localnodeVal;
            var windowSize = 'height=560, width=1300';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#irlplog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "irlplog.php";
            var windowName = "IRLPLog" + localnodeVal;
            var windowSize = 'height=560, width=1100';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#webacclog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "webacclog.php";
            var windowName = "WebAccessLog" + localnodeVal;
            var windowSize = 'height=560, width=1400';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#weberrlog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "weberrlog.php";
            var windowName = "WebErrorLog" + localnodeVal;
            var windowSize = 'height=560, width=1400';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#openpigpio').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "pi-gpio.php";
            var windowName = "Pi-GPIO" + localnodeVal;
            var windowSize = 'height=900, width=900';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#openbanallow').click(function(event) {
            event.preventDefault();
            var node = $('#node').val();
            var localnode = $('#localnode').val();
            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "node-ban-allow-intro.php?node=" + node + "&localnode=" + localnode;
            var windowName = "Ban-Allow" + localnode;
            var windowSize = 'height=700, width=750';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#opensimpleusb').click(function(event) {
            event.preventDefault();
            var node = $('#node').val();
            var localnode = $('#localnode').val();
            if (!localnode) {
                alertify.error("Local node must be selected or available.");
                return;
            }
            var url = "simpleusb-control-intro.php?node=" + node + "&localnode=" + localnode;
            var windowName = "Simpleusb-tune-menu " + localnode;
            var windowSize = 'height=700, width=1000';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

        $('#smlog').click(function(event) {
            event.preventDefault();
            var localnodeVal = $('#localnode').val() || 'unknown';
            var url = "smlog.php";
            var windowName = "SMLog" + localnodeVal;
            var windowSize = 'height=560, width=1200';
            var myWindow = window.open(url, windowName, windowSize);
            if (myWindow) {
                myWindow.moveTo(20, 20);
            }
        });

    } // --- End of Event Handlers for Logged-In Users ---


    // --- Event Handlers Available Regardless of Login State ---

    // Click on table cell to populate connection form
    $('table').on('click', 'td.nodeNum', function(event) {
        const remoteNodeInput = $('#connect_form #node');
        const localNodeInput = $('#connect_form #localnode');

        if ($('#connect_form').is(':visible')) {
            if (remoteNodeInput.length) {
                remoteNodeInput.val($(this).text().trim());
            }

            var tableId = $(this).closest('table').attr('id');
            if (tableId) {
                var idarr = tableId.split('_');
                if (idarr.length > 1 && localNodeInput.length) {
                    localNodeInput.val(idarr[1]);
                }
            }
        }
    });

    // Show login dialog
    $("#loginlink").click(function(event) {
        event.preventDefault();
        clearForm();
        showLogin();
    });

}); // --- End of $(document).ready ---


// --- Login Form Helper Functions ---

function clearForm() {
    const checkbox = document.getElementById("checkbox");
    if (checkbox) {
        checkbox.checked = false;
        const passwdField = document.getElementById("passwd");
        if (passwdField) {
            passwdField.type = "password";
        }
    }

    $('#myform :input')
        .not(':button, :submit, :reset, :hidden, :radio')
        .val('');
    $('#myform input[type="checkbox"]').prop('checked', false);
}

function showPW() {
    var x = document.getElementById("passwd");
    var y = document.getElementById("user");
    var checkbox = document.getElementById("checkbox");

    if (y && x && checkbox) {
        if (y.value.trim()) {
            if (x.type === "password") {
                x.type = "text";
                checkbox.checked = true;
            } else {
                x.type = "password";
                checkbox.checked = false;
            }
        } else {
            x.type = "password";
            checkbox.checked = false;
        }
    }
}

function hideLogin() {
    $('#login').hide();
}

function showLogin() {
    $('#login').show();
    const userField = $('#login #user');
    if (userField.length) {
        userField.focus();
    }
}

function validate_login() {
    var user = $("#user").val();
    var passwd = $("#passwd").val();

    if (!user.trim() || !passwd.trim()) {
        alertify.error("Username and Password are required.");
        return false;
    }

    $.ajax({
        type: "POST",
        url: "login.php",
        data: {
            'user': user,
            'passwd': passwd
        },
        dataType: 'text',
        success: function(response) {
            hideLogin();
            if (response && response.trim().substr(0, 5) != 'Sorry') {
                alertify.success("<p style=\"font-size:28px;\"><b>Welcome " + user + "!</b></p>");
                sleep(2000).then(() => {
                    window.location.reload();
                });
            } else {
                alertify.error(response.trim() || "Sorry, Login Failed!");
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            hideLogin();
            alertify.error("Login request failed: " + textStatus + (errorThrown ? " - " + errorThrown : ""));
        }
    });

    return false;
}

function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}
