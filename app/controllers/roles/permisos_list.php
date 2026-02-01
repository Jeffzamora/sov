<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json; charset=utf-8');

// Seguridad: este endpoint expone la matriz de permisos.
// Recomendado: solo ADMINISTRADOR.
if (function_exists('require_admin')) {
    require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
    require_perm($pdo, 'roles.permisos.ver', $URL . '/index.php');
}

try {
    ensure_session();

    $idRol = input_int('id_rol', true, 'GET');

    // Permisos activos
    $p = $pdo->prepare("SELECT id_permiso, clave, descripcion FROM tb_permisos  ORDER BY clave ASC");
    $p->execute();
    $perms = $p->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Asignados
    $r = $pdo->prepare("SELECT id_permiso FROM tb_roles_permisos WHERE id_rol = ?");
    $r->execute([$idRol]);
    $assigned = $r->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $assignedMap = [];
    foreach ($assigned as $pid) $assignedMap[(int)$pid] = true;

    // Agrupar por módulo (antes del punto)
    $groups = [];
    foreach ($perms as $row) {
        $idp = (int)$row['id_permiso'];
        $clave = (string)$row['clave'];
        $mod = explode('.', $clave, 2)[0] ?? 'otros';
        if (!isset($groups[$mod])) $groups[$mod] = [];
        $groups[$mod][] = [
            'id_permiso' => $idp,
            'clave' => $clave,
            'descripcion' => (string)($row['descripcion'] ?? ''),
            'checked' => !empty($assignedMap[$idp]),
        ];
    }
    ksort($groups);

    echo json_encode(['ok' => true, 'data' => ['groups' => $groups]], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
