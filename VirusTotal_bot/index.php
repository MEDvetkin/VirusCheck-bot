<?php

//created the bot by MEDvetkin

$apiKey = '330196ad7e515b9d69debf2938d98db07b0c39182e5b986a84657868221ceceb';
$telegramToken = '8149056967:AAH2WTAf2ihj_hZmv6G7fVjD4ueBbHQpi-g';
$telegramApiUrl = "https://api.telegram.org/bot$telegramToken/";

$content = file_get_contents("php://input");
$update = json_decode($content, true);

$translations = [
    'en' => [
        'file_received' => "File successfully sent for analysis. Analysis ID: %s",
        'error' => "Error: %s",
        'send_file' => "Please send a file for analysis.",
        'api_error' => "An error occurred while contacting the VirusTotal API. Please try again later.",
        'network_error' => "Network error. Please check your internet connection."
    ],
    'ru' => [
        'file_received' => "Файл успешно отправлен на анализ. ID анализа: %s",
        'error' => "Ошибка: %s",
        'send_file' => "Пожалуйста, отправьте файл для анализа.",
        'api_error' => "Произошла ошибка при обращении к API VirusTotal. Попробуйте позже.",
        'network_error' => "Ошибка сети. Пожалуйста, проверьте подключение к интернету."
    ],
    // Добавьте другие языки по мере необходимости
];

if (isset($update['message']['text'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'];

    if ($text === '/start') {
        sendMessage($chatId, "Добро пожаловать! Я ваш бот.");
    } 
    // Добавьте дополнительные условия для других команд
}

if (isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $languageCode = $update['message']['from']['language_code']; // По умолчанию английский

    if (isset($update['message']['document'])) {
        $fileId = $update['message']['document']['file_id'];
        $file = json_decode(file_get_contents($telegramApiUrl . "getFile?file_id=$fileId"), true);
        $filePath = $file['result']['file_path'];
        $fileUrl = "https://api.telegram.org/file/bot$telegramToken/$filePath";

        // Загрузка файла на VirusTotal
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.virustotal.com/api/v3/files");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "x-apikey: $apiKey",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $fileUrl]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);
        if ($httpCode === 200) {
            if (isset($result['data']['id'])) {
                $analysisId = $result['data']['id'];
                sendMessage($chatId, sprintf($translations[$languageCode]['file_received'], $analysisId));
            } else {
                sendMessage($chatId, sprintf($translations[$languageCode]['error'], $result['message']));
            }
        } else {
            sendMessage($chatId, $translations[$languageCode]['api_error']);
        }
    } else {
        sendMessage($chatId, $translations[$languageCode]['send_file']);
    }
}

function sendMessage($chatId, $message) {
    global $telegramApiUrl;
    file_get_contents($telegramApiUrl . "sendMessage?chat_id=$chatId&text=" . urlencode($message));
}
