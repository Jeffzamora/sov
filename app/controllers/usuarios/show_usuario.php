<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

// Permiso
require_admin($pdo, $URL . '/index.php');

// GET: ?id=123
$id_usuario_get = input_int('id', true, 'GET');

$sql = "
  SELECT
    u.id_usuario,
    u.nombres,
    u.email,
    u.id_rol,
    UPPER(u.estado) AS estado,
    COALESCE(r.rol, '(Sin rol)') AS rol
  FROM tb_usuarios u
  LEFT JOIN tb_roles r ON r.id_rol = u.id_rol
  WHERE u.id_usuario = :id
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id_usuario_get]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

if (!$usuario) {
    // Define valores seguros para evitar warnings en la vista
    $nombres = '';
    $email   = '';
    $rol     = '';
    $estado  = '';
} else {
    // Normalización defensiva
    $usuario['id_usuario'] = (int)($usuario['id_usuario'] ?? 0);
    $usuario['id_rol']     = (int)($usuario['id_rol'] ?? 0);
    $usuario['nombres']    = (string)($usuario['nombres'] ?? '');
    $usuario['email']      = (string)($usuario['email'] ?? '');
    $usuario['rol']        = (string)($usuario['rol'] ?? '(Sin rol)');
    $usuario['estado']     = strtoupper((string)($usuario['estado'] ?? 'ACTIVO'));
    if (!in_array($usuario['estado'], ['ACTIVO', 'INACTIVO'], true)) {
        $usuario['estado'] = 'ACTIVO';
    }

    // Compatibilidad con vistas antiguas
    $nombres = $usuario['nombres'];
    $email   = $usuario['email'];
    $rol     = $usuario['rol'];
    $estado  = $usuario['estado'];
}
