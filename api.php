<?php
header('Content-Type: application/json');

// Archivos de almacenamiento
$messagesFile = 'messages.json';
$settingsFile = 'settings.json';

// Crear archivos si no existen
if (!file_exists($messagesFile)) file_put_contents($messagesFile, json_encode([]));
if (!file_exists($settingsFile)) {
    file_put_contents($settingsFile, json_encode([
        "color" => "#ff0000",
        "autoDelete" => 0
    ]));
}

// Leer datos
$messages = json_decode(file_get_contents($messagesFile), true);
$settings = json_decode(file_get_contents($settingsFile), true);

// Acción solicitada
$action = $_GET['action'] ?? '';

// 🔹 AGREGAR MENSAJE
if ($action === 'add') {
    $text = trim($_POST['text'] ?? '');

    if ($text !== '') {
        $messages[] = [
            "id" => time() . rand(100,999),
            "text" => htmlspecialchars($text, ENT_QUOTES, 'UTF-8'),
            "time" => time()
        ];

        file_put_contents($messagesFile, json_encode($messages));
    }

    echo json_encode(["status" => "ok"]);
    exit;
}

// 🔹 OBTENER DATOS
if ($action === 'get') {

    // Auto borrar (24h)
    if (!empty($settings['autoDelete'])) {
        $ahora = time();
        $messages = array_filter($messages, function($m) use ($ahora) {
            return ($ahora - $m['time']) < 86400;
        });
        file_put_contents($messagesFile, json_encode(array_values($messages)));
    }

    echo json_encode([
        "messages" => array_values($messages),
        "settings" => $settings
    ]);
    exit;
}

// 🔹 BORRAR UNO
if ($action === 'delete') {
    $id = $_GET['id'] ?? '';

    $messages = array_filter($messages, function($m) use ($id) {
        return $m['id'] != $id;
    });

    file_put_contents($messagesFile, json_encode(array_values($messages)));

    echo json_encode(["status" => "deleted"]);
    exit;
}

// 🔹 BORRAR TODOS
if ($action === 'deleteAll') {
    file_put_contents($messagesFile, json_encode([]));
    echo json_encode(["status" => "all_deleted"]);
    exit;
}

// 🔹 GUARDAR AJUSTES
if ($action === 'settings') {
    $color = $_POST['color'] ?? '#ff0000';
    $autoDelete = $_POST['autoDelete'] ?? 0;

    $settings = [
        "color" => $color,
        "autoDelete" => (int)$autoDelete
    ];

    file_put_contents($settingsFile, json_encode($settings));

    echo json_encode(["status" => "settings_saved"]);
    exit;
}

// 🔹 DEFAULT
echo json_encode(["error" => "Acción no válida"]);