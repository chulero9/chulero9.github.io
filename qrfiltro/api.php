<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

$dataFile = "messages.json";
$settingsFile = "settings.json";

// Crear archivos si no existen
if (!file_exists($dataFile)) file_put_contents($dataFile, json_encode([]));
if (!file_exists($settingsFile)) {
    file_put_contents($settingsFile, json_encode([
        "color" => "#ff0000",
        "auto_delete" => false,
        "auto_approve" => true
    ]));
}

// Leer datos
$messages = json_decode(file_get_contents($dataFile), true);
$settings = json_decode(file_get_contents($settingsFile), true);

$action = $_GET['action'] ?? 'get';

// 🔹 RESPUESTA PRINCIPAL
if ($action === "get") {
    echo json_encode([
        "messages" => $messages,
        "settings" => $settings
    ]);
    exit;
}

// 🔹 CAMBIAR ESTADO
if ($action === "toggle") {
    $id = $_POST['id'];

    foreach ($messages as &$msg) {
        if ($msg['id'] == $id) {
            $msg['active'] = !$msg['active'];

            // 📲 WhatsApp opcional
            sendWhatsApp("51961763299", "📺 Mensaje actualizado: " . $msg['text']);
        }
    }

    file_put_contents($dataFile, json_encode($messages));
    echo json_encode(["status" => "ok"]);
    exit;
}

// 🔹 ELIMINAR MENSAJE
if ($action === "delete") {
    $id = $_POST['id'];

    $messages = array_filter($messages, function($m) use ($id) {
        return $m['id'] != $id;
    });

    file_put_contents($dataFile, json_encode(array_values($messages)));

    sendWhatsApp("519XXXXXXXX", "🗑️ Un mensaje fue eliminado");

    echo json_encode(["status" => "ok"]);
    exit;
}

// 🔹 BORRAR TODO
if ($action === "delete_all") {
    file_put_contents($dataFile, json_encode([]));

    sendWhatsApp("519XXXXXXXX", "⚠️ Todos los mensajes fueron eliminados");

    echo json_encode(["status" => "ok"]);
    exit;
}

// 🔹 GUARDAR SETTINGS
if ($action === "settings") {

    $settings['color'] = $_POST['color'] ?? "#ff0000";
    $settings['auto_delete'] = isset($_POST['auto_delete']);
    $settings['auto_approve'] = isset($_POST['auto_approve']);

    file_put_contents($settingsFile, json_encode($settings));

    echo json_encode(["status" => "ok"]);
    exit;
}

// 🔹 AGREGAR MENSAJE (extra útil)
if ($action === "add") {
    $text = $_POST['text'] ?? '';

    if ($text != '') {
        $messages[] = [
            "id" => uniqid(),
            "text" => $text,
            "active" => $settings['auto_approve'],
            "time" => time()
        ];

        file_put_contents($dataFile, json_encode($messages));

        sendWhatsApp("51961763299", "🆕 Nuevo mensaje recibido: " . $text);
    }

    echo json_encode(["status" => "ok"]);
    exit;
}

// 📲 FUNCIÓN WHATSAPP (UltraMsg)
function sendWhatsApp($phone, $message) {

    $instanceId = "TU_INSTANCE_ID";
    $token = "TU_TOKEN";

    $url = "https://api.ultramsg.com/$instanceId/messages/chat";

    $data = [
        "token" => $token,
        "to" => $phone,
        "body" => $message
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "POST",
            "content" => json_encode($data),
        ]
    ];

    $context  = stream_context_create($options);
    file_get_contents($url, false, $context);
}
?>
