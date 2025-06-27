<?php
$token = getenv("TELEGRAM_BOT_TOKEN");
$apiUrl = "https://api.telegram.org/bot$token/";

function sendMessage($chatId, $message) {
    global $apiUrl;
    $url = $apiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($message);
    file_get_contents($url);
}

function getMagneticStormStatus() {
    $url = "https://services.swpc.noaa.gov/json/planetary_k_index_1m.json";
    $data = file_get_contents($url);
    $json = json_decode($data, true);

    if (is_array($json) && count($json) > 0) {
        $lastEntry = end($json);
        $kIndex = $lastEntry['kp_index']; // Индекс
        $timeTag = $lastEntry['time_tag']; // Время в ISO формате

        // Преобразуем дату/время
        $date = new DateTime($timeTag, new DateTimeZone('UTC'));
        $date->setTimezone(new DateTimeZone('Europe/Moscow'));
        $formattedTime = $date->format("H:i:s d.m.Y");
        
        if ($kIndex >= 5) {
            return "⚠️ Сейчас наблюдается магнитная буря!\nУровень K-индекса в обсерватории Боулдера США: $kIndex\nВремя измерения по МСК: $formattedTime.";
        } else {
            return "✅ Магнитной бури сейчас нет.\nУровень K-индекса в обсерватории Боулдера США: $kIndex\nВремя измерения по МСК: $formattedTime.";
        }
    } else {
        return "❌ Не удалось получить данные о магнитной активности.";
    }
}


$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    if (strpos($text, "/start") !== false) {
        sendMessage($chatId, "Привет! Я бот, который сообщает, есть ли сейчас магнитная буря. Напишите /storm, чтобы узнать.");
    } elseif (strpos($text, "/storm") !== false) {
        $status = getMagneticStormStatus();
        sendMessage($chatId, $status);
    } else {
        sendMessage($chatId, "Я не понимаю эту команду. Напишите /storm, чтобы узнать о магнитных бурях.");
    }
}
?>
