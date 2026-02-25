<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'cajas.ver', $URL . '/');
require_once __DIR__ . '/_caja_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// 1) Seguridad: requerir sesión
$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'No autorizado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Permiso opcional
if (function_exists('ui_can') && !ui_can('cajas.ver')) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso denegado'], JSON_UNESCAPED_UNICODE);
    exit;
}

// (Opcional) aquí puedes validar permisos/roles si tu sistema los maneja
// if (!user_can($id_usuario, 'cajas.ver_estado')) { ... }

function json_out(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Diagnóstico: múltiples cajas abiertas (si existe helper)
    $openCount = function_exists('caja_count_abiertas') ? caja_count_abiertas($pdo) : null;

    $caja = caja_abierta_actual($pdo);
    if (!$caja) {
        json_out(200, [
            'ok' => true,
            'data' => null,
            'meta' => [
                'open_count' => $openCount,
            ],
        ]);
    }

    $id_caja = (int)($caja['id_caja'] ?? 0);
    $tot = caja_calcular_totales($pdo, $id_caja);

    // 2) Minimizar datos expuestos
    $cajaPublica = [
        'id_caja' => $id_caja,
        'estado' => (string)($caja['estado'] ?? ''),
        'fecha_apertura' => (string)($caja['fecha_apertura'] ?? ''),
        'fecha_cierre' => (string)($caja['fecha_cierre'] ?? ''),
        'monto_inicial' => (float)($caja['monto_inicial'] ?? 0),
        // Si necesitas mostrarlo en UI: usuario apertura/cierre
        'usuario_apertura_id' => (int)($caja['usuario_apertura_id'] ?? 0),
        'usuario_cierre_id' => (int)($caja['usuario_cierre_id'] ?? 0),
    ];

    json_out(200, [
        'ok' => true,
        'data' => [
            'caja' => $cajaPublica,
            'totales' => $tot,
        ],
        'meta' => [
            'open_count' => $openCount,
            // útil para frontend (polling)
            'server_time' => date('c'),
        ],
    ]);

} catch (Throwable $e) {
    $rid = bin2hex(random_bytes(6)); // request id corto
    error_log("Caja estado error [$rid]: " . $e->getMessage());

    json_out(500, [
        'ok' => false,
        'error' => 'Error interno',
        'request_id' => $rid,
    ]);
}
