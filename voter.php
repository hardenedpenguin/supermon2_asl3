<?php

include("session.inc");
include("header.inc");

// Get node numbers from GET parameter, sanitize, and split into an array
$passedNodesInput = @trim(strip_tags($_GET['node']));
$passedNodes = [];
if (!empty($passedNodesInput)) {
    $passedNodes = explode(',', $passedNodesInput);
}

// Check if at least one node number was provided
if (empty($passedNodes) || empty($passedNodes[0])) {
    // Terminate script with an error message if no node is provided
    die("Please provide a voter node number (e.g., voter.php?node=1234 or voter.php?node=1234,5678)");
}
?>

<script>
    // Prevent IE caching for AJAX requests
    $.ajaxSetup({
        cache: false,
        timeout: 3000 // Set a timeout for AJAX requests (3 seconds)
    });

    // Execute when the DOM is fully loaded
    $(document).ready(function() {
        // Check if the browser supports Server-Sent Events (SSE)
        if (typeof(EventSource) !== "undefined") {
            <?php
            // Loop through each provided node number
            foreach ($passedNodes as $node) {
                // Sanitize node number for safe use in JS/HTML (ensure it's just digits)
                $node = filter_var($node, FILTER_SANITIZE_NUMBER_INT);
                if (empty($node)) continue; // Skip if node number is invalid after sanitization
            ?>
                // Create a new EventSource for each node to listen for updates
                var source<?php echo $node; ?> = new EventSource("voterserver.php?node=<?php echo $node; ?>");

                // Define the handler for receiving messages from the server
                source<?php echo $node; ?>.onmessage = function(event) {
                    // Update the content of the corresponding div with data from the server
                    $("#link_list_<?php echo $node; ?>").html(event.data);
                };
            <?php
            } // End foreach loop
            ?>
        } else {
            // Display a message if SSE is not supported by the browser
            $("#link_list_<?php echo filter_var($passedNodes[0], FILTER_SANITIZE_NUMBER_INT); ?>").html("Sorry, your browser does not support server-sent events...");
        }
    });
</script>

<br />

<?php
// Create a placeholder div for each node's data
foreach ($passedNodes as $node) {
    $node = filter_var($node, FILTER_SANITIZE_NUMBER_INT);
     if (empty($node)) continue;
?>
    <div id="link_list_<?php echo $node; ?>">Loading data for node <?php echo $node; ?>...</div>
    <br />
<?php
}
?>

<div style="max-width: 600px; margin-top: 20px; text-align: left; line-height: 1.5;">
    <p>
        The numbers displayed next to each station indicate the relative signal strength (RSSI) received by this voter node.
        The value ranges from 0 to 255, representing a dynamic range of approximately 30dB.
        A value of zero indicates that no signal is currently being received from that station by this voter.
    </p>
    <p>The color of the bars indicates the type and status of the RTCM client:</p>
</div>

<!-- Legend section -->
<div style="width: 280px; text-align: left; margin-top: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
    <div style="margin-bottom: 5px;">
        <span style="display: inline-block; width: 20px; height: 15px; background-color: #0099FF; margin-right: 10px; vertical-align: middle;"></span>
        <span style="vertical-align: middle;">Blue: Voting station (candidate).</span>
    </div>
    <div style="margin-bottom: 5px;">
        <span style="display: inline-block; width: 20px; height: 15px; background-color: greenyellow; margin-right: 10px; vertical-align: middle;"></span>
        <span style="vertical-align: middle;">Green: Voted station (selected).</span>
    </div>
    <div>
        <span style="display: inline-block; width: 20px; height: 15px; background-color: cyan; margin-right: 10px; vertical-align: middle;"></span>
        <span style="vertical-align: middle;">Cyan: Non-voting mix station.</span>
    </div>
</div>

<br>

<?php
include("footer.inc");
?>