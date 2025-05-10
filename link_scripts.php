<?php

global $parms, $Displayed_Nodes, $Display_Count, $Show_All, $Show_Detail;

$php_Displayed_Nodes = intval($Displayed_Nodes);
$php_Display_Count = intval($Display_Count);
$php_Show_All = intval($Show_All);
$php_Show_Detail = intval($Show_Detail);

$html_safe_parms = htmlspecialchars($parms, ENT_QUOTES, 'UTF-8');

?>
<script type="text/javascript">
function toTop() {
    window.scrollTo(0, 0);
}

function getFirstValidInfo(currentValue, newValue) {
    if (!currentValue && newValue && newValue !== 'N/A') {
        return newValue;
    }
    return currentValue;
}

$(document).ready(function() {
  var sdetail = <?php echo $php_Show_Detail; ?>;
  var headerColspan = (sdetail == 1) ? 7 : 5;

  if(typeof(EventSource) !== "undefined") {

    var eventSourceUrl = "allmon-sse.php?nodes=<?php echo $html_safe_parms; ?>";
    var source = new EventSource(eventSourceUrl);
    
    var spinChars = ["*", "|", "/", "-", "\\"];
    var spinIndex = 0;

    source.addEventListener('nodes', function(event) {
        var tabledata;
        try {
            tabledata = JSON.parse(event.data);
        } catch (e) {
            return;
        }

        var ndisp = <?php echo $php_Displayed_Nodes; ?>;
        var sdisp = <?php echo $php_Display_Count; ?>;
        var sall = <?php echo $php_Show_All; ?>;

        for (var localNode in tabledata) {
            if (!tabledata.hasOwnProperty(localNode)) continue;

            var tablehtml = '';
            var total_nodes = 0;
            var shown_nodes = 0;
            
            var cos_keyed = 0;
            var tx_keyed = 0;
            var cpu_temp = '', cpu_up = '', cpu_load = '', alert_msg = '', wx_msg = '', logs_msg = '';
            
            var has_remote_nodes_data = (tabledata[localNode] && tabledata[localNode].remote_nodes && tabledata[localNode].remote_nodes.length > 0);

            if (has_remote_nodes_data) {
                for (var i = 0; i < tabledata[localNode].remote_nodes.length; i++) {
                    var rowData = tabledata[localNode].remote_nodes[i];
                    if (rowData.cos_keyed == 1) cos_keyed = 1;
                    if (rowData.tx_keyed == 1) tx_keyed = 1;

                    cpu_temp = getFirstValidInfo(cpu_temp, rowData.cpu_temp);
                    cpu_up = getFirstValidInfo(cpu_up, rowData.cpu_up);
                    cpu_load = getFirstValidInfo(cpu_load, rowData.cpu_load);
                    alert_msg = getFirstValidInfo(alert_msg, rowData.ALERT);
                    wx_msg = getFirstValidInfo(wx_msg, rowData.WX);
                    logs_msg = getFirstValidInfo(logs_msg, rowData.LOGS);
                }
            }

            var headerClass = 'gColor';
            var headerStatus = 'Idle';

            if (cos_keyed === 1 && tx_keyed === 1) {
                headerClass = 'bColor';
                headerStatus = 'COS-Detected and PTT-Keyed (Full-Duplex)';
            } else if (cos_keyed === 1) {
                headerClass = 'lColor';
                headerStatus = 'COS-Detected';
            } else if (tx_keyed === 1) {
                headerClass = 'tColor';
                headerStatus = 'PTT-Keyed';
            }

            tablehtml += '<tr class="' + headerClass + '">';
            tablehtml += '<td align="center">' + localNode + '</td>';

            var statusCellContent = '<b>' + headerStatus;
             if (alert_msg) statusCellContent += '<br>' + alert_msg;
             if (wx_msg) statusCellContent += '<br>' + wx_msg;
             var systemInfo = [];
             if (cpu_temp || cpu_up) systemInfo.push('CPU=' + (cpu_temp || '?') + ' - ' + (cpu_up || '?'));
             if (cpu_load) systemInfo.push(cpu_load);
             if (logs_msg) systemInfo.push(logs_msg);
             if (systemInfo.length > 0) {
                 statusCellContent += '<br>' + systemInfo.join('<br>');
             }
             statusCellContent += '</b>';

            tablehtml += '<td align="center" colspan="1">' + statusCellContent + '</td>';
            if (headerColspan > 2) {
                tablehtml += '<td colspan="' + (headerColspan - 2) + '"></td>';
            }
            tablehtml += '</tr>';

            var hasConnections = false;
             if (has_remote_nodes_data) {
                for (var row = 0; row < tabledata[localNode].remote_nodes.length; row++) {
                    var rowData = tabledata[localNode].remote_nodes[row];

                    if (rowData.info === "NO CONNECTION" || rowData.node == 1 || typeof rowData.node === 'undefined') {
                        continue;
                    }

                    hasConnections = true;
                    total_nodes++;

                    var showThisRow = true;
                    if (ndisp > 0 && total_nodes > ndisp) {
                       showThisRow = false;
                    }
                    if (sall == 0 && rowData.last_keyed == "Never" && shown_nodes > 0) {
                        showThisRow = false;
                    }

                    if (showThisRow) {
                        shown_nodes++;
                        var rowClass = '';
                        if (rowData.keyed == 'yes') {
                             rowClass = (rowData.mode == 'R') ? 'rxkColor' : 'rColor';
                        } else if (rowData.mode == 'C') {
                             rowClass = 'cColor';
                        } else if (rowData.mode == 'R') {
                             rowClass = 'rxColor';
                        }

                        tablehtml += '<tr' + (rowClass ? ' class="' + rowClass + '"' : '') + '>';
                        var nodeNumId = 't' + localNode + 'c0' + 'r' + row;
                        tablehtml += '<td id="' + nodeNumId + '" align="center" class="nodeNum" onclick="toTop()">' + (rowData.node || 'N/A') + '</td>';
                        
                        var infoStr = rowData.info ? String(rowData.info).trim() : '';
                        var ipStr = rowData.ip ? String(rowData.ip).trim() : '';
                        var nodeInfoText = infoStr || ipStr || 'N/A';
                        tablehtml += '<td>' + nodeInfoText + '</td>';

                        if (sdetail == 1) {
                            tablehtml += '<td align="center" id="lkey' + localNode + '_' + row + '">' + (rowData.last_keyed || ' ') + '</td>';
                        }
                        tablehtml += '<td align="center">' + (rowData.link || 'N/A') + '</td>';
                        tablehtml += '<td align="center">' + (rowData.direction || 'N/A') + '</td>';
                        if (sdetail == 1) {
                            tablehtml += '<td align="right" id="elap' + localNode + '_' + row + '">' + (rowData.elapsed || ' ') + '</td>';
                        }

                        var modeMap = { 'R': 'RX Only', 'T': 'Transceive', 'C': 'Connecting' };
                        var modeText = modeMap[rowData.mode] || rowData.mode || 'N/A';
                        tablehtml += '<td align="center">' + modeText + '</td>';
                        tablehtml += '</tr>';
                    }
                }
            }

            if (!hasConnections) {
                 var noConnectionMessage = "    Waiting for data...";
                 if (has_remote_nodes_data) {
                    if (tabledata[localNode].remote_nodes.some(node => node.info === "NO CONNECTION")) {
                         noConnectionMessage = "    No Connections.";
                    } else {
                        noConnectionMessage = "    No connections to display (or waiting).";
                    }
                 } else {
                     if (tabledata[localNode] && typeof tabledata[localNode].remote_nodes !== 'undefined') {
                         if (tabledata[localNode].remote_nodes === null || typeof tabledata[localNode].remote_nodes === 'undefined') {
                            noConnectionMessage = "    No connection data received.";
                         }
                     } else {
                        noConnectionMessage = "    No connection data received.";
                     }
                 }
                 if (noConnectionMessage) {
                    tablehtml += '<tr><td colspan="' + headerColspan + '">' + noConnectionMessage + '</td></tr>';
                 }
            }

            if (sdisp === 1 && total_nodes > 0) {
                 tablehtml += '<tr>';
                 if (shown_nodes == total_nodes) {
                    tablehtml += '<td colspan="' + headerColspan + '">    ' + total_nodes + ' node' + (total_nodes > 1 ? 's':'') + ' connected';
                 } else {
                    tablehtml += '<td colspan="' + headerColspan + '">    ' + shown_nodes + ' shown of ' + total_nodes + ' node' + (total_nodes > 1 ? 's':'') + ' connected';
                 }
                 tablehtml += '    <a href="#" onclick="toTop(); return false;">^^^</a></td></tr>';
            }
            $('#table_' + localNode + ' tbody:first').html(tablehtml);
        }
    });

    if (sdetail == 1) {
        source.addEventListener('nodetimes', function(event) {
             var timedata;
             try {
                timedata = JSON.parse(event.data);
            } catch (e) {
                return;
            }

            for (var localNode in timedata) {
                 if (!timedata.hasOwnProperty(localNode) || !timedata[localNode].remote_nodes) continue;
                 for (var row = 0; row < timedata[localNode].remote_nodes.length; row++) {
                     var timeRowData = timedata[localNode].remote_nodes[row];
                     var lastKeyedID = '#lkey' + localNode + '_' + row;
                     var elapsedID = '#elap' + localNode + '_' + row;

                     var $lastKeyedCell = $(lastKeyedID);
                     if ($lastKeyedCell.length && typeof timeRowData.last_keyed !== 'undefined' && timeRowData.last_keyed !== null) {
                          $lastKeyedCell.text( timeRowData.last_keyed );
                     }
                     var $elapsedCell = $(elapsedID);
                     if ($elapsedCell.length && typeof timeRowData.elapsed !== 'undefined' && timeRowData.elapsed !== null) {
                          $elapsedCell.text( timeRowData.elapsed );
                      }
                 }
            }
            $('#spinny').html(spinChars[spinIndex]);
            spinIndex = (spinIndex + 1) % spinChars.length;
        });
    }

    source.addEventListener('connection', function(event) {
         try {
            var statusdata = JSON.parse(event.data);
             if(statusdata.node && statusdata.status) {
                var tableID = '#table_' + statusdata.node;
                var $table = $(tableID);
                 if ($table.length) {
                     $table.find('tbody:first').html('<tr><td colspan="' + headerColspan + '">' + statusdata.status + '</td></tr>');
                 }
             }
        } catch (e) { }
    });

     source.onerror = function(event) {
         var $tableBodies = $('.gridtable tbody:first, .gridtable-large tbody:first');
         if ($tableBodies.length === 0) {
             $tableBodies = $('table tbody:first');
         }
         $tableBodies.html('<tr><td colspan="' + headerColspan + '">Connection to server lost. Attempting to reconnect...</td></tr>');
    };

  } else {
      var $container = $("#list_link");
      if ($container.length === 0) $container = $("body");
      $container.html("<p>Sorry, your browser does not support server-sent events needed for live updates.</p>");
  }
});
</script>