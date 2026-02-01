<?php
try {
    $sql = "
    SELECT id_permiso, clave, descripcion, estado
    FROM tb_permisos
    ORDER BY clave ASC
  ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $permisos_datos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    foreach ($permisos_datos as &$p) {
        $p['id_permiso'] = (int)($p['id_permiso'] ?? 0);
        $p['clave'] = (string)($p['clave'] ?? '');
        $p['descripcion'] = (string)($p['descripcion'] ?? '');
        if (!in_array($p['estado'], ['ACTIVO', 'INACTIVO'], true)) $p['estado'] = 'ACTIVO';
    }
    unset($p);
} catch (Throwable $e) {
    error_log('Permisos listado error: ' . $e->getMessage());
    $permisos_datos = [];
}
