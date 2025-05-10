<?php
function send_sse_message(string $event, $data, bool $doFlush = true) {
    echo "event: $event\n" .
         "data: " . json_encode($data) . "\n\n";

    if ($doFlush) {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
?>