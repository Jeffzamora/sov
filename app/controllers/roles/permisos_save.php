<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

// Seguridad: solo ADMINISTRADOR (o permiso específico)
if (function_exists('require_admin')) {
    require_admin($pdo, $URL . '/index.php');
} elseif (function_exists('require_perm')) {
    require_perm($pdo, 'roles.permisos.editar', $URL . '/index.php');
}

try {
    $idRol = input_int('id_rol', true);
    if ($idRol <= 0) throw new RuntimeException('Rol inválido.');

    // Verifica que exista el rol y su estado (si tu tabla tiene estado)
    $stRol = $pdo->prepare("SELECT id_rol, rol, COALESCE(UPPER(estado),'ACTIVO') AS estado FROM tb_roles WHERE id_rol=? LIMIT 1");
    $stRol->execute([$idRol]);
    $rolRow = $stRol->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$rolRow) throw new RuntimeException('Rol no encontrado.');

    $rolNombre = strtoupper(trim((string)($rolRow['rol'] ?? '')));
    $rolEstado = strtoupper(trim((string)($rolRow['estado'] ?? 'ACTIVO')));
    if ($rolEstado !== 'ACTIVO') throw new RuntimeException('No se puede editar permisos de un rol inactivo.');

    // Reglas de negocio: ADMINISTRADOR siempre tiene todo.
    // Si quieres permitir edición manual, comenta este bloque.
    if ($rolNombre === 'ADMINISTRADOR') {
        throw new RuntimeException('El rol ADMINISTRADOR no se edita. Tiene acceso total por defecto.');
    }
    $arr = $_POST['permisos'] ?? [];
    if (!is_array($arr)) $arr = [];

    // Normalizar IDs
    $ids = [];
    foreach ($arr as $v) {
        if (is_numeric($v)) $ids[] = (int)$v;
    }
    $ids = array_values(array_unique(array_filter($ids)));

    // Validar que existan los permisos enviados (evita FK / basura)
    if (!empty($ids)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT id_permiso FROM tb_permisos WHERE id_permiso IN ($in)");
        $st->execute($ids);
        $valid = $st->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $valid = array_map('intval', $valid);
        $ids = array_values(array_intersect($ids, $valid));
    }

    $pdo->beginTransaction();

    // Limpia y re-inserta (simple y confiable)
    $pdo->prepare("DELETE FROM tb_roles_permisos WHERE id_rol = ?")->execute([$idRol]);

    if (!empty($ids)) {
        $ins = $pdo->prepare("INSERT INTO tb_roles_permisos (id_rol, id_permiso) VALUES (:r, :p)");
        foreach ($ids as $pid) $ins->execute([':r' => $idRol, ':p' => $pid]);
    }

    $pdo->commit();

    // IMPORTANTE: si el rol editado es el del usuario en sesión, refresca cache permisos
    ensure_session();
    if (!empty($_SESSION['_perms_role']) && (int)$_SESSION['_perms_role'] === $idRol) {
        if (function_exists('load_role_perms')) {
            $_SESSION['_perms'] = load_role_perms($pdo, $idRol);
        }
    }

    if (is_ajax_request()) json_response(['ok' => true]);
    redirect($URL . '/roles', 'Permisos actualizados.', 'success');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
    redirect($URL . '/roles', $e->getMessage() ?: 'No se pudo guardar permisos.', 'danger');
}
