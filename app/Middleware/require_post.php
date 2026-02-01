<?php
// app/Middleware/require_post.php
declare(strict_types=1);

function require_post(): void
{
  if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido. Use POST.');
  }
}
