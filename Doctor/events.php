<?php
// events.php

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$ts = isset($_GET['ts']) ? (int) $_GET['ts'] : 0;
$currentTs = time();

// Initial connection — send current timestamp
if ($ts === 0) {
    echo "event: init\n";
    echo "data: {$currentTs}\n\n";
    flush();
    exit;
}

// Example: send a dummy update every time
echo "event: message\n";
echo "data: " . json_encode(["msg" => "New event", "ts" => $currentTs]) . "\n\n";
flush();
?>
