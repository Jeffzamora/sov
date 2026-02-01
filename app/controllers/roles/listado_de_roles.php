<?php
try {
    $sql = "
      SELECT id_rol, rol, COALESCE(estado,'ACTIVO') AS estado
      FROM tb_roles
      WHERE COALESCE(estado,'ACTIVO') <> 'INACTIVO'
      ORDER BY rol ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $roles_datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($roles_datos as &$r) {
        $r['id_rol'] = (int)($r['id_rol'] ?? 0);
        $r['rol']    = (string)($r['rol'] ?? '');
        $r['estado'] = strtoupper((string)($r['estado'] ?? 'ACTIVO'));
    }
    unset($r);
} catch (Throwable $e) {
    error_log('Roles listado error: ' . $e->getMessage());
    $roles_datos = [];
}

