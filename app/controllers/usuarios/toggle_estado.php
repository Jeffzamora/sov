<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

require_post();
csrf_verify();
// Permiso
require_admin($pdo, $URL . '/index.php');

ensure_session();
$idSesion = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($idSesion <= 0) {
    redirect($URL . '/login', 'Sesión no válida.', 'danger');
}

try {
    $id_usuario = input_int('id_usuario', true);
    $estadoReq  = strtoupper(trim(input_str('estado', 20, true)));

    if (!in_array($estadoReq, ['ACTIVO', 'INACTIVO'], true)) {
        throw new RuntimeException('Estado inválido.');
    }

    // Permisos RBAC (ajusta si quieres permisos separados)
    // - Activar/editar: usuarios.actualizar
    // - Desactivar: usuarios.eliminar
    if ($estadoReq === 'INACTIVO') {
        require_perm($pdo, 'usuarios.eliminar', $URL . '/usuarios');
    } else {
        require_perm($pdo, 'usuarios.actualizar', $URL . '/usuarios');
    }

    // Evita auto-desactivación
    if ($estadoReq === 'INACTIVO' && $idSesion === (int)$id_usuario) {
        throw new RuntimeException('No puedes desactivar tu propio usuario.');
    }

    // Verificar usuario actual
    $st = $pdo->prepare("SELECT id_usuario, estado, id_rol FROM tb_usuarios WHERE id_usuario=? LIMIT 1");
    $st->execute([$id_usuario]);
    $target = $st->fetch(PDO::FETCH_ASSOC);
    if (!$target) {
        throw new RuntimeException('Usuario no encontrado.');
    }

    $estadoActual = strtoupper((string)($target['estado'] ?? ''));
    if ($estadoActual === $estadoReq) {
        // Idempotente (mejor UX)
        if (function_exists('is_ajax_request') && is_ajax_request()) {
            json_response(['ok' => true, 'estado' => $estadoReq, 'message' => 'Sin cambios.'], 200);
        }
        redirect($URL . '/usuarios/', 'Sin cambios.', 'info');
    }

    // (Opcional recomendado) no desactivar el último administrador
    if ($estadoReq === 'INACTIVO') {
        $idRolTarget = (int)($target['id_rol'] ?? 0);
        if ($idRolTarget > 0) {
            try {
                $r = $pdo->prepare("SELECT rol FROM tb_roles WHERE id_rol=? LIMIT 1");
                $r->execute([$idRolTarget]);
                $rolNombre = strtoupper((string)($r->fetchColumn() ?: ''));
                if ($rolNombre === 'ADMIN' || $rolNombre === 'ADMINISTRADOR') {
                    $c = $pdo->query("
            SELECT COUNT(*)
              FROM tb_usuarios u
              INNER JOIN tb_roles r ON r.id_rol = u.id_rol
             WHERE u.estado='ACTIVO'
               AND (UPPER(r.rol)='ADMIN' OR UPPER(r.rol)='ADMINISTRADOR')
          ");
                    $adminsActivos = (int)($c->fetchColumn() ?: 0);
                    if ($adminsActivos <= 1) {
                        throw new RuntimeException('No puedes desactivar el último administrador activo.');
                    }
                }
            } catch (Throwable $ignore) {
                // si falla la validación por nombre de rol, no rompemos el flujo base
            }
        }
    }

    // Update
    $stmt = $pdo->prepare("UPDATE tb_usuarios SET estado = :st WHERE id_usuario = :id LIMIT 1");
    $stmt->execute([':st' => $estadoReq, ':id' => $id_usuario]);

    // Auditoría
    if (function_exists('auditoria_log')) {
        $accion = ($estadoReq === 'INACTIVO') ? 'DESACTIVAR' : 'ACTIVAR';
        auditoria_log($pdo, $accion, 'tb_usuarios', (int)$id_usuario, 'Cambio de estado a ' . $estadoReq);
    }

    if ($stmt->rowCount() < 1) {
        throw new RuntimeException('No se pudo actualizar el estado.');
    }

    if (function_exists('is_ajax_request') && is_ajax_request()) {
        json_response(['ok' => true, 'estado' => $estadoReq, 'message' => 'Estado actualizado.'], 200);
    }

    redirect($URL . '/usuarios/', 'Estado actualizado.', 'success');
} catch (Throwable $e) {
    $msg = $e->getMessage() ?: 'No se pudo actualizar el estado.';

    if ($e instanceof PDOException) {
        error_log('toggle_estado error: ' . $e->getMessage());
        $msg = 'No se pudo actualizar el estado.';
    }

    if (function_exists('is_ajax_request') && is_ajax_request()) {
        json_response(['ok' => false, 'error' => $msg], 422);
    }

    redirect($URL . '/usuarios/', $msg, 'danger');
}
