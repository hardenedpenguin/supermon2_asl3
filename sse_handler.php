<?php
// sse_handler.php: Utility for sending Server-Sent Events

/**
 * Sends a message formatted as a Server-Sent Event.
 *
 * @param string $event The event name.
 * @param mixed $data The data to send (will be JSON encoded).
 * @param bool $flush Perform ob_flush() and flush(). Defaults to true.
 */
function send_sse_message(string $event, $data, bool $doFlush = true) {
    echo "event: " . $event . "\n";
    echo 'data: ' . json_encode($data) . "\n\n";
    if ($doFlush) {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
?>
