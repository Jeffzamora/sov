<?php
// app/controllers/usuarios/listado_de_usuarios.php
// Requiere que $pdo exista (config.php incluido por el caller)
// Permiso
require_admin($pdo, $URL . '/index.php');

$sql = "
  SELECT
    u.id_usuario,
    u.nombres,
    u.email,
    u.id_rol,
    u.estado,
    COALESCE(r.rol, '(Sin rol)') AS rol
  FROM tb_usuarios u
  LEFT JOIN tb_roles r ON r.id_rol = u.id_rol
  ORDER BY u.id_usuario DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$usuarios_datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Normalización defensiva (evita warnings en la vista)
foreach ($usuarios_datos as &$u) {
   $u['id_usuario'] = (int)($u['id_usuario'] ?? 0);
   $u['id_rol']     = (int)($u['id_rol'] ?? 0);
   $u['nombres']    = (string)($u['nombres'] ?? '');
   $u['email']      = (string)($u['email'] ?? '');
   $u['rol']        = (string)($u['rol'] ?? '(Sin rol)');
   $u['estado']     = strtoupper((string)($u['estado'] ?? 'ACTIVO'));
   if ($u['estado'] !== 'ACTIVO' && $u['estado'] !== 'INACTIVO') {
      $u['estado'] = 'ACTIVO';
   }
}
unset($u);
