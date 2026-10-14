<?php
declare(strict_types=1);
$config = require __DIR__ . '/config.php';
ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set($config['timezone']);
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>$isHttps,'httponly'=>true,'samesite'=>'Lax']);
    session_start();
}
try {
    $pdo = new PDO(
        'mysql:host='.$config['db_host'].';dbname='.$config['db_name'].';charset=utf8mb4',
        $config['db_user'],
        $config['db_pass'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]
    );
    $pdo->exec("SET time_zone = '+01:00'");
} catch (PDOException $e) {
    error_log('CMB database connection: '.$e->getMessage());
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Service temporarily unavailable. Check database setup.']);
    exit;
}
