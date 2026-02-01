<?php
// app/Security/Csrf.php
declare(strict_types=1);

function csrf_token(): string
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
  if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
  }
  return (string)$_SESSION['_csrf'];
}

function csrf_field(): string
{
  $t = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
  return '<input type="hidden" name="_csrf" value="'.$t.'">';
}

function csrf_verify(?string $token = null): void
{
  if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
  }
  $token = $token ?? ($_POST['_csrf'] ?? '');
  $ok = is_string($token) && isset($_SESSION['_csrf']) && hash_equals((string)$_SESSION['_csrf'], (string)$token);
  if (!$ok) {
    http_response_code(403);
    exit('Acceso denegado (CSRF).');
  }
}
