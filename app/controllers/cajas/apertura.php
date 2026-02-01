<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/_caja_lib.php';

require_post();
csrf_verify();
ensure_session();
require_perm($pdo, 'cajas.aperturar', $URL . '/cajas');

// Seguridad: usuario desde sesión
$id_usuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($id_usuario <= 0) {
    redirect($URL . '/login', 'Sesión no válida. Inicia sesión nuevamente.', 'danger');
}

$monto_inicial = input_decimal('monto_inicial', true);
$nota = input_str('nota', 255, false);

$monto_inicial_f = round((float)$monto_inicial, 2);
if ($monto_inicial_f < 0) {
    redirect($URL . '/cajas', 'El monto inicial no puede ser negativo.', 'danger');
}

$nota = trim((string)$nota);
$nota = ($nota === '') ? null : $nota;

$pdo->beginTransaction();
try {
    // Concurrencia: bloquear y validar que no exista caja abierta
    $stmt = $pdo->query("SELECT id_caja FROM tb_cajas ORDER BY id_caja DESC LIMIT 1 FOR UPDATE");
    $stmt->fetchColumn();

    $caja = caja_abierta_actual($pdo);
    if ($caja) {
        throw new RuntimeException('Ya existe una caja abierta. Cierre la caja actual antes de abrir una nueva.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tb_cajas (usuario_apertura_id, monto_inicial, estado, nota) VALUES (:u,:m,'abierta',:nota)"
    );
    $stmt->execute([
        ':u' => $id_usuario,
        ':m' => $monto_inicial_f,
        ':nota' => $nota,
    ]);

    $pdo->commit();
    redirect($URL . '/cajas', 'Caja aperturada correctamente.', 'success');
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('Caja apertura error: ' . $e->getMessage());
    redirect($URL . '/cajas', $e->getMessage() ?: 'No se pudo aperturar la caja.', 'danger');
}
