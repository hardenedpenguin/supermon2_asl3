<?php
/**
 * link_scripts.php: Client-side JavaScript for the Supermon node display page.
 *
 * Handles dynamic updates of the node tables using Server-Sent Events (SSE).
 * Connects to allmon-sse.php to receive live status data and updates the HTML table
 * without requiring a full page refresh.
 */

// Make PHP variables from link.php available.
global $parms, $Displayed_Nodes, $Display_Count, $Show_All, $Show_Detail;

// Convert display settings to integers for reliable JavaScript use.
$php_Displayed_Nodes = intval($Displayed_Nodes);
$php_Display_Count = intval($Display_Count);
$php_Show_All = intval($Show_All);
$php_Show_Detail = intval($Show_Detail);

// Sanitize the node list parameter for safe inclusion in the URL (prevents XSS).
$html_safe_parms = htmlspecialchars($parms, ENT_QUOTES, 'UTF-8');

?>
<script type="text/javascript">
/**
 * Scrolls the browser window to the top.
 * Used when clicking on a node number in the table.
 */
function toTop() {
    window.scrollTo(0, 0);
}

/**
 * Main execution block, runs after the document is ready.
 */
$(document).ready(function() {

  // Check if the browser supports Server-Sent Events (SSE).
  if(typeof(EventSource) !== "undefined") {

    // Construct the URL for the SSE connection to the server-side script.
    var eventSourceUrl = "allmon-sse.php?nodes=<?php echo $html_safe_parms; ?>";

    // Create the EventSource object to establish the connection.
    var source = new EventSource(eventSourceUrl);
    // Initialize a variable for a simple spinning text animation in detailed view.
    var spinny = "*";

    /**
     * Event Listener for 'nodes' messages: receives full node status updates
     * and rebuilds the node table content.
     */
    source.addEventListener('nodes', function(event) {
        var tabledata;
        try {
            // Parse the JSON string received from the server.
            tabledata = JSON.parse(event.data);
        } catch (e) {
            // If parsing fails, stop processing this update.
            return;
        }

        var sdetail = <?php echo $php_Show_Detail; ?>; // Current detail view setting (1 = Detailed, 0 = Compact).

        // Loop through each local node included in the data (e.g., '54605', '54606').
        for (var localNode in tabledata) {
            // Skip properties inherited from the prototype chain.
            if (!tabledata.hasOwnProperty(localNode)) continue;

            var tablehtml = '';         // Accumulates HTML table rows.
            var total_nodes = 0;        // Total connected remote nodes.
            var shown_nodes = 0;        // Nodes displayed after filtering.
            var ndisp = <?php echo $php_Displayed_Nodes; ?>; // Max nodes to show per table.
            var sdisp = <?php echo $php_Display_Count; ?>;   // Show node count footer? (0 or 1)
            var sall = <?php echo $php_Show_All; ?>;       // Show "Never" keyed nodes? (0 or 1)

            // Variables for overall local node header status.
            var cos_keyed = 0;          // Receiver detecting signal?
            var tx_keyed = 0;           // Transmitter keyed?
            // Variables for system info in the header.
            var cpu_temp = '';
            var cpu_up = '';
            var cpu_load = '';
            var alert_msg = '';
            var wx_msg = '';
            var logs_msg = '';
            // Check if remote node data exists for this local node.
            var has_remote_nodes = (tabledata[localNode] && tabledata[localNode].remote_nodes && tabledata[localNode].remote_nodes.length > 0);

            // Determine Header Status and Collect System Info
            // Loop through remote nodes once to find overall keying and first available system info.
            if (has_remote_nodes) {
                for (var i = 0; i < tabledata[localNode].remote_nodes.length; i++) {
                    var rowData = tabledata[localNode].remote_nodes[i];
                    // Check keying status.
                    if (rowData.cos_keyed == 1) cos_keyed = 1;
                    if (rowData.tx_keyed == 1) tx_keyed = 1;

                    // Grab first non-empty system value found.
                    if (!cpu_temp && rowData.cpu_temp && rowData.cpu_temp !== 'N/A') cpu_temp = rowData.cpu_temp;
                    if (!cpu_up && rowData.cpu_up && rowData.cpu_up !== 'N/A') cpu_up = rowData.cpu_up;
                    if (!cpu_load && rowData.cpu_load && rowData.cpu_load !== 'N/A') cpu_load = rowData.cpu_load;
                    if (!alert_msg && rowData.ALERT && rowData.ALERT !== 'N/A') alert_msg = rowData.ALERT;
                    if (!wx_msg && rowData.WX && rowData.WX !== 'N/A') wx_msg = rowData.WX;
                    if (!logs_msg && rowData.LOGS && rowData.LOGS !== 'N/A') logs_msg = rowData.LOGS;
                }
            }

            // Build Header Row HTML
            var headerClass = 'gColor'; // Default: Idle
            var headerStatus = 'Idle';
            var headerColspan = (sdetail == 1) ? 7 : 5; // Colspan depends on view mode

            // Determine status text and CSS class based on keying.
            if (cos_keyed === 1 && tx_keyed === 1) { // Full-Duplex
                headerClass = 'bColor';
                headerStatus = 'COS-Detected and PTT-Keyed (Full-Duplex)';
            } else if (cos_keyed === 1) { // Receiving
                headerClass = 'lColor';
                headerStatus = 'COS-Detected';
            } else if (tx_keyed === 1) { // Transmitting
                headerClass = 'tColor';
                headerStatus = 'PTT-Keyed';
            }

            tablehtml += '<tr class="' + headerClass + '">';
            tablehtml += '<td align="center">' + localNode + '</td>'; // Local Node number

            // Prepare status cell content with optional system info.
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

            tablehtml += '<td align="center" colspan="1">' + statusCellContent + '</td>'; // Status cell
            // Add empty cells to fill remaining columns.
            if (headerColspan > 2) {
                tablehtml += '<td colspan="' + (headerColspan - 2) + '"></td>';
            }
            tablehtml += '</tr>'; // End header row

            // Build Data Rows for Connected Nodes
            var hasConnections = false; // Track if any valid connection rows are processed.
             if (has_remote_nodes) {
                // Loop through each remote node connected to this local node.
                for (var row = 0; row < tabledata[localNode].remote_nodes.length; row++) {
                    var rowData = tabledata[localNode].remote_nodes[row];

                    // Skip special/internal rows (e.g., "NO CONNECTION", local status row often node '1').
                    if (rowData.info === "NO CONNECTION" || rowData.node == 1 || typeof rowData.node === 'undefined') {
                        continue;
                    }

                    hasConnections = true;
                    total_nodes++;

                    // Apply Display Filters
                    var showThisRow = true;
                    // Filter 1: Limit number of displayed nodes (if ndisp > 0).
                    if (ndisp > 0 && total_nodes > ndisp) {
                       showThisRow = false;
                    }
                    // Filter 2: Hide "Never" keyed if $Show_All is off (except first visible).
                    if (sall == 0 && rowData.last_keyed == "Never" && shown_nodes > 0) {
                        showThisRow = false;
                    }

                    // If the row passes filters, generate its HTML.
                    if (showThisRow) {
                        shown_nodes++;

                        // Determine row background color based on keying/mode.
                        var rowClass = '';
                        if (rowData.keyed == 'yes') { // Remote node transmitting?
                             rowClass = (rowData.mode == 'R') ? 'rxkColor' : 'rColor'; // Different color for RX-only link.
                        } else if (rowData.mode == 'C') { // Connection in progress?
                             rowClass = 'cColor';
                        } else if (rowData.mode == 'R') { // RX-only link (idle)?
                             rowClass = 'rxColor';
                        }

                        tablehtml += '<tr' + (rowClass ? ' class="' + rowClass + '"' : '') + '>';

                        // Generate Table Cells (Columns)
                        // Col 1: Remote Node Number (clickable)
                        var nodeNumId = 't' + localNode + 'c0' + 'r' + row; // Unique ID using row index
                        tablehtml += '<td id="' + nodeNumId + '" align="center" class="nodeNum" onclick="toTop()">' + (rowData.node || 'N/A') + '</td>';

                        // Col 2: Node Info (Description or IP)
                        var nodeInfoText = 'N/A';
                        if (rowData.info && rowData.info.trim() !== "") {
                            nodeInfoText = rowData.info;
                        } else if (rowData.ip && rowData.ip.trim() !== "") {
                            nodeInfoText = rowData.ip; // Fallback to IP
                        }
                        tablehtml += '<td>' + nodeInfoText + '</td>';

                        // Columns shown only in Detailed View (sdetail == 1)
                        if (sdetail == 1) {
                            // Col 3: Received (Last Keyed) - ID for 'nodetimes' updates
                            tablehtml += '<td align="center" id="lkey' + localNode + '_' + row + '">' + (rowData.last_keyed || ' ') + '</td>';
                        }

                        // Col 4 (Detail) / 3 (Compact): Link Type
                        tablehtml += '<td align="center">' + (rowData.link || 'N/A') + '</td>';

                        // Col 5 (Detail) / 4 (Compact): Direction (In/Out)
                        tablehtml += '<td align="center">' + (rowData.direction || 'N/A') + '</td>';

                        // Columns shown only in Detailed View (sdetail == 1)
                        if (sdetail == 1) {
                            // Col 6: Connected (Elapsed) - ID for 'nodetimes' updates
                            tablehtml += '<td align="right" id="elap' + localNode + '_' + row + '">' + (rowData.elapsed || ' ') + '</td>';
                        }

                        // Col 7 (Detail) / 5 (Compact): Mode
                        var modeText = rowData.mode || 'N/A';
                        if (rowData.mode == 'R') modeText = 'RX Only';
                        else if (rowData.mode == 'T') modeText = 'Transceive';
                        else if (rowData.mode == 'C') modeText = 'Connecting';
                        tablehtml += '<td align="center">' + modeText + '</td>';

                        tablehtml += '</tr>'; // End data row
                    } // End if(showThisRow)
                } // End loop through remote_nodes
            } // End if(has_remote_nodes)

            // Handle Case of No Displayable Connections
            // If no 'real' connection rows were found or all were filtered out.
            if (!hasConnections) {
                 var noConnectionMessage = "    Waiting for data..."; // Initial default
                 if (has_remote_nodes && tabledata[localNode].remote_nodes.length > 0) {
                    // Check if only internal/filtered nodes were present.
                    var firstRealNode = tabledata[localNode].remote_nodes.find(node => node.node != 1 && node.info !== "NO CONNECTION");
                    if (!firstRealNode) { // No displayable nodes found
                        if (tabledata[localNode].remote_nodes.some(node => node.info === "NO CONNECTION")) {
                             noConnectionMessage = "    No Connections."; // Server explicitly said no connections
                        } else {
                            noConnectionMessage = "    No connections to display (or waiting)."; // Only internal node 1 or similar
                        }
                    } else {
                         // Connections exist but were filtered out. Footer shows count. Don't add this row.
                         noConnectionMessage = '';
                    }
                 } else if (!has_remote_nodes) {
                     // Server sent no remote_nodes array for this local node.
                      noConnectionMessage = "    No connection data received.";
                 }
                 // Display the message spanning all columns if a message was determined.
                 if (noConnectionMessage) {
                    tablehtml += '<tr><td colspan="' + headerColspan + '">' + noConnectionMessage + '</td></tr>';
                 }
            }

            // Add Footer Row (Node Count Summary)
            // If enabled ($Display_Count == 1) and there are connected nodes.
            if (sdisp === 1 && total_nodes > 0) {
                 tablehtml += '<tr>';
                 // Show count summary, indicating if nodes were filtered.
                 if (shown_nodes == total_nodes) {
                    tablehtml += '<td colspan="' + headerColspan + '">    ' + total_nodes + ' node' + (total_nodes > 1 ? 's':'') + ' connected';
                 } else {
                    tablehtml += '<td colspan="' + headerColspan + '">    ' + shown_nodes + ' shown of ' + total_nodes + ' node' + (total_nodes > 1 ? 's':'') + ' connected';
                 }
                 // Add 'scroll to top' link.
                 tablehtml += '    <a href="#" onclick="toTop(); return false;">^^^</a></td></tr>';
            }

            // Update the HTML Table
            // Replace the content of the specific table body for this localNode.
            $('#table_' + localNode + ' tbody:first').html(tablehtml);

        } // End loop through localNode in tabledata
    }); // End 'nodes' event listener

    /**
     * Event Listener for 'nodetimes' messages: receives only updated time values
     * (Last Keyed, Elapsed) and updates only those specific table cells efficiently.
     * Only active if detailed view is enabled.
     */
    if (<?php echo $php_Show_Detail; ?> == 1) {
        source.addEventListener('nodetimes', function(event) {
             var timedata;
             try {
                timedata = JSON.parse(event.data);
            } catch (e) {
                return; // Stop if JSON is invalid.
            }

            // Loop through each local node in the time update data.
            for (var localNode in timedata) {
                 // Skip inherited properties and ensure remote_nodes data exists.
                 if (!timedata.hasOwnProperty(localNode) || !timedata[localNode].remote_nodes) continue;

                 // Loop through time updates for each remote node connection, using the row index.
                 for (var row = 0; row < timedata[localNode].remote_nodes.length; row++) {
                     var timeRowData = timedata[localNode].remote_nodes[row];
                     // Construct the unique cell IDs generated in the 'nodes' event listener.
                     var lastKeyedID = '#lkey' + localNode + '_' + row;
                     var elapsedID = '#elap' + localNode + '_' + row;

                     // Find cells by ID and update their text content if they exist and data is provided.
                     // This avoids errors if the table structure changed or time data is missing.
                     var $lastKeyedCell = $(lastKeyedID);
                     if ($lastKeyedCell.length && typeof timeRowData.last_keyed !== 'undefined' && timeRowData.last_keyed !== null) {
                          $lastKeyedCell.text( timeRowData.last_keyed );
                     }
                     var $elapsedCell = $(elapsedID);
                     if ($elapsedCell.length && typeof timeRowData.elapsed !== 'undefined' && timeRowData.elapsed !== null) {
                          $elapsedCell.text( timeRowData.elapsed );
                      }
                 } // End loop through rows
            } // End loop through local nodes

            // Update the simple text spinner animation.
            if (spinny == "*") spinny = "|";
            else if (spinny == "|") spinny = "/";
            else if (spinny == "/") spinny = "-";
            else if (spinny == "-") spinny = "\\";
            else if (spinny == "\\") spinny = "|";
            else spinny = "*";
            $('#spinny').html(spinny); // Update the spinner element.
        }); // End 'nodetimes' event listener
    } // End if(detailed view)

    /**
     * Event Listener for 'connection' messages: handles special status messages from the server,
     * typically replacing the table body with the status (e.g., "Connecting...").
     */
    source.addEventListener('connection', function(event) {
         try {
            var statusdata = JSON.parse(event.data);
             // Check if the message contains the target node and status text.
             if(statusdata.node && statusdata.status) {
                var tableID = '#table_' + statusdata.node;
                var $table = $(tableID);
                 // Check if the target table exists on the page.
                 if ($table.length) {
                    var headerColspan = (<?php echo $php_Show_Detail; ?> == 1) ? 7 : 5;
                    // Replace table body content with the status message.
                     $table.find('tbody:first').html('<tr><td colspan="' + headerColspan + '">' + statusdata.status + '</td></tr>');
                 }
             }
        } catch (e) {
            // Error parsing JSON.
         }
    }); // End 'connection' event listener

    /**
     * Error Handler for the EventSource connection.
     * Called if the connection to allmon-sse.php is lost or fails.
     */
     source.onerror = function(event) {
        var headerColspan = (<?php echo $php_Show_Detail; ?> == 1) ? 7 : 5;
         // Display an error message in *all* node table bodies on the page.
         var $tableBodies = $('.gridtable tbody:first, .gridtable-large tbody:first');
         if ($tableBodies.length === 0) {
             // Fallback selector if specific classes aren't found.
             $tableBodies = $('table tbody:first');
         }
         $tableBodies.html('<tr><td colspan="' + headerColspan + '">Connection to server lost. Attempting to reconnect...</td></tr>');
         // Note: The browser typically attempts to reconnect automatically.
    }; // End onerror handler

  } else {
      // This runs if the browser does NOT support Server-Sent Events.
      // Display an incompatibility message in a relevant container.
      var $container = $("#list_link"); // Assumed container ID
      if ($container.length === 0) {
          $container = $("body"); // Fallback container
      }
      $container.html("<p>Sorry, your browser does not support server-sent events needed for live updates.</p>");
  }
}); // End $(document).ready()
</script>