<?php
require_once __DIR__ . '/../../config.php';

require_post();
csrf_verify();

try {
    $id = input_int('id_permiso', true);
    $estado = strtoupper(trim(input_str('estado', 20, true)));

    if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
        throw new RuntimeException('Estado inválido.');
    }

    $stmt = $pdo->prepare("UPDATE tb_permisos SET estado=:st WHERE id_permiso=:id LIMIT 1");
    $stmt->execute([':st' => $estado, ':id' => $id]);

    if (is_ajax_request()) json_response(['ok' => true, 'estado' => $estado]);
    redirect($URL . '/permisos', 'Estado actualizado.', 'success');
} catch (Throwable $e) {
    if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
    redirect($URL . '/permisos', $e->getMessage() ?: 'No se pudo actualizar.', 'danger');
}
