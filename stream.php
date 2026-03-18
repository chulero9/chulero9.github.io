<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$messagesFile = 'messages.json';
$settingsFile = 'settings.json';

$lastUpdate = 0;

while (true) {

    clearstatcache();

    $currentUpdate = filemtime($messagesFile);

    if ($currentUpdate !== $lastUpdate) {
        $lastUpdate = $currentUpdate;

        $messages = json_decode(file_get_contents($messagesFile), true);
        $settings = json_decode(file_get_contents($settingsFile), true);

        echo "data: " . json_encode([
            "messages" => $messages,
            "settings" => $settings
        ]) . "\n\n";

        ob_flush();
        flush();
    }

    sleep(1);
}