<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();

try {
    if (function_exists('require_perm')) {
        require_perm($pdo, 'categorias.actualizar', $URL . '/index.php');
    }

    if (!db_column_exists($pdo, 'tb_categorias', 'estado')) {
        throw new RuntimeException('Tu tabla no tiene columna estado.');
    }

    $id = input_int('id_categoria', true);
    $estado = strtoupper(trim(input_str('estado', 20, true)));

    if (!in_array($estado, ['ACTIVO', 'INACTIVO'], true)) {
        throw new RuntimeException('Estado inválido.');
    }

    // Si se va a INACTIVO, evita desactivar si hay productos asociados.
    if ($estado === 'INACTIVO' && db_table_exists($pdo, 'tb_almacen') && db_column_exists($pdo, 'tb_almacen', 'id_categoria')) {
        $st = $pdo->prepare("SELECT COUNT(*) FROM tb_almacen WHERE id_categoria=?");
        $st->execute([$id]);
        $c = (int)$st->fetchColumn();
        if ($c > 0) {
            throw new RuntimeException('No se puede desactivar: hay productos asociados a esta categoría.');
        }
    }

    // fyh_actualizacion es opcional; si no existe, actualiza solo estado.
    $fyh = date('Y-m-d H:i:s');
    if (db_column_exists($pdo, 'tb_categorias', 'fyh_actualizacion')) {
        $stmt = $pdo->prepare("UPDATE tb_categorias SET estado=:st, fyh_actualizacion=:fyh WHERE id_categoria=:id LIMIT 1");
        $stmt->execute([':st' => $estado, ':fyh' => $fyh, ':id' => $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE tb_categorias SET estado=:st WHERE id_categoria=:id LIMIT 1");
        $stmt->execute([':st' => $estado, ':id' => $id]);
    }
    if ($stmt->rowCount() < 1) throw new RuntimeException('No se pudo actualizar el estado.');

    if (is_ajax_request()) json_response(['ok' => true, 'estado' => $estado]);
    redirect($URL . '/categorias/', 'Estado actualizado.', 'success');
} catch (Throwable $e) {
    if (is_ajax_request()) json_response(['ok' => false, 'error' => $e->getMessage()], 422);
    redirect($URL . '/categorias/', $e->getMessage(), 'danger');
}
