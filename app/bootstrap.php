<?php
/** Inicialización común: configuración, sesiones y clases. Compatible con PHP 7.4. */
$config = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($config['app']['timezone']);

require_once __DIR__ . '/Http.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/PersonValidator.php';
require_once __DIR__ . '/PersonRepository.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
