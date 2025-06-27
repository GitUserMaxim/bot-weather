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
        $kIndex = $lastEntry['kp_index'];
        $timeTag = $lastEntry['time_tag'];

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

function getCurrentWeather() {
    $latitude = 55.75;   // Москва
    $longitude = 37.61;
    $url = "https://api.open-meteo.com/v1/forecast?latitude=$latitude&longitude=$longitude&current=temperature_2m,weathercode,cloudcover,windspeed_10m,winddirection_10m,precipitation,rain,snowfall,is_day&timezone=Europe%2FMoscow";

    $data = file_get_contents($url);
    $json = json_decode($data, true);

    if (isset($json['current'])) {
        $w = $json['current'];
        $temperature = $w['temperature_2m'];
        $cloudcover = $w['cloudcover'];
        $windSpeed = $w['windspeed_10m'];
        $windDir = $w['winddirection_10m'];
        $precip = $w['precipitation'];
        $rain = $w['rain'];
        $snow = $w['snowfall'];
        $isDay = $w['is_day'] ? "День" : "Ночь";

        $conditions = [
            0 => "Ясно", 1 => "Преимущественно ясно", 2 => "Переменная облачность",
            3 => "Пасмурно", 45 => "Туман", 48 => "Инейный туман",
            51 => "Слабая морось", 53 => "Умеренная морось", 55 => "Сильная морось",
            61 => "Слабый дождь", 63 => "Умеренный дождь", 65 => "Сильный дождь",
            71 => "Слабый снег", 73 => "Умеренный снег", 75 => "Сильный снег",
            95 => "Гроза"
        ];
        $condition = $conditions[$w['weathercode']] ?? "Неизвестно";

        return "🌦 Погода в Москве:
🌡 Температура: {$temperature}°C
☁ Облачность: {$cloudcover}%
💨 Ветер: {$windSpeed} км/ч, направление {$windDir}°
🌧 Осадки: {$precip} мм (дождь: {$rain} мм, снег: {$snow} мм)
☀ Сейчас: {$isDay}
📡 Состояние: {$condition}";
    } else {
        return "❌ Не удалось получить данные о погоде.";
    }
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (isset($update["message"])) {
    $chatId = $update["message"]["chat"]["id"];
    $text = $update["message"]["text"];

    if (strpos($text, "/start") !== false) {
        sendMessage($chatId, "Привет! Я бот, который сообщает:
- 🌍 Магнитную активность — /storm
- 🌦 Текущую погоду в Москве — /weather");
    } elseif (strpos($text, "/storm") !== false) {
        $status = getMagneticStormStatus();
        sendMessage($chatId, $status);
    } elseif (strpos($text, "/weather") !== false) {
        $weather = getCurrentWeather();
        sendMessage($chatId, $weather);
    } else {
        sendMessage($chatId, "Команда не распознана. Доступные команды:\n/storm — Магнитная буря\n/weather — Погода");
    }
}
?>
