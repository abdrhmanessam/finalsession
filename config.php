<?php

if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    die("⚠️ لا يمكنك الوصول إلى هذا الملف مباشرة!");
}

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'elearn');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');


try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    file_put_contents('error_log.txt', date('Y-m-d H:i:s') . " - فشل الاتصال بقاعدة البيانات: " . $e->getMessage() . "\n", FILE_APPEND);
    die("⚠️ حدث خطأ أثناء الاتصال بقاعدة البيانات.");
}
?>
