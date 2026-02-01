<?php
// app/Helpers/bootstrap.php
declare(strict_types=1);

// Cargar config (del parche prioridad 1)
require_once __DIR__ . '/../config.php';

// Endurecer sesión (si no está iniciado)
if (session_status() !== PHP_SESSION_ACTIVE) {
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'domain' => '',
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax',
    ]);
  }
  session_start();
}

// Helpers
require_once __DIR__ . '/../Security/Csrf.php';
require_once __DIR__ . '/../Security/Input.php';
require_once __DIR__ . '/../Security/Upload.php';
require_once __DIR__ . '/../Middleware/require_post.php';
