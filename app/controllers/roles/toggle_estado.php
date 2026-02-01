<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
    $id_rol = input_int('id_rol', true);
    $estado = strtoupper(trim(input_str('estado', 20, true)));

    if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) throw new RuntimeException('Estado inválido.');

    $st = $pdo->prepare("SELECT rol FROM tb_roles WHERE id_rol=:id LIMIT 1");
    $st->execute([':id' => $id_rol]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Rol no encontrado.');
    if (strtoupper((string)$row['rol']) === 'ADMINISTRADOR') throw new RuntimeException('ADMINISTRADOR está protegido.');

    if ($estado === 'INACTIVO') {
        $st = $pdo->prepare("SELECT COUNT(*) FROM tb_usuarios WHERE id_rol=:id");
        $st->execute([':id' => $id_rol]);
        if ((int)$st->fetchColumn() > 0) throw new RuntimeException('No se puede desactivar: hay usuarios asignados.');
    }

    $stmt = $pdo->prepare("UPDATE tb_roles SET estado=:st, fyh_actualizacion=NOW() WHERE id_rol=:id LIMIT 1");
    $stmt->execute([':st' => $estado, ':id' => $id_rol]);

    if (is_ajax_request()) json_response(['ok' => true, 'estado' => $estado]);
    redirect($URL . '/roles/', 'Estado actualizado.', 'success');
} catch (Throwable $e) {
    if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
    redirect($URL . '/roles/', $e->getMessage() ?: 'No se pudo actualizar.', 'danger');
}
