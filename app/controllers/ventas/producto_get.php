<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../helpers/bootstrap.php';
require_once __DIR__ . '/../../helpers/db_schema.php';
require_once __DIR__ . '/../../helpers/devoluciones.php';

require_post();
csrf_verify();
ensure_session();

$map = require __DIR__ . '/../../helpers/devoluciones_map.php';

$ventaId = (int)($_POST['id_venta'] ?? 0);
$items = $_POST['items'] ?? []; // [{id_producto, cantidad}, ...]

if ($ventaId <= 0 || !is_array($items) || count($items) === 0) {
    error_log('Datos inválidos', 422);
    exit;
}

$idUsuario = (int)($_SESSION['sesion_id_usuario'] ?? 0);
if ($idUsuario <= 0) error_log('Sesión inválida', 401);

$tDev = $map['t_devoluciones'];
$tDet = $map['t_devoluciones_det'];

try {
    $pdo->beginTransaction();

    // 1) Traer detalle de venta para validar cantidades + obtener precio
    $qDet = $pdo->prepare("
    SELECT id_producto, cantidad, precio_venta
      FROM tb_ventas_detalle
     WHERE id_venta = ?
  ");
    $qDet->execute([$ventaId]);
    $ventaDet = $qDet->fetchAll(PDO::FETCH_ASSOC);

    if (!$ventaDet) {
        throw new Exception('No existe detalle para esa venta');
    }

    // index por producto
    $vd = [];
    foreach ($ventaDet as $r) {
        $vd[(int)$r['id_producto']] = [
            'cantidad' => (float)$r['cantidad'],
            'precio' => (float)$r['precio_venta'],
        ];
    }

    // 2) Calcular total devolución + validar cantidades
    $total = 0.0;
    $normalized = [];

    foreach ($items as $it) {
        $pid = (int)($it['id_producto'] ?? 0);
        $cant = (float)($it['cantidad'] ?? 0);

        if ($pid <= 0 || $cant <= 0) continue;
        if (!isset($vd[$pid])) throw new Exception("Producto $pid no pertenece a la venta");

        // ya devuelto antes?
        // suma devoluciones previas por producto
        $sumPrev = $pdo->prepare("
      SELECT COALESCE(SUM({$map['d_cantidad']}),0) AS s
        FROM {$tDet} d
        JOIN {$tDev} h ON h.{$map['c_id_devolucion']} = d.{$map['d_id_devolucion']}
       WHERE h.{$map['c_id_venta']} = ?
         AND d.{$map['d_id_producto']} = ?
    ");
        $sumPrev->execute([$ventaId, $pid]);
        $prev = (float)($sumPrev->fetchColumn() ?: 0);

        $max = $vd[$pid]['cantidad'] - $prev;
        if ($cant > $max + 1e-9) {
            throw new Exception("Cantidad excede lo disponible para devolver (producto $pid). Disponible: $max");
        }

        $precio = $vd[$pid]['precio'];
        $total += ($cant * $precio);

        $normalized[] = [
            'id_producto' => $pid,
            'cantidad' => $cant,
            'precio' => $precio,
        ];
    }

    if ($total <= 0 || count($normalized) === 0) {
        throw new Exception('No hay items válidos para devolver');
    }

    // 3) Insert cabecera devolución (usando tu tabla real vía mapa)
    $motivo = trim((string)($_POST['motivo'] ?? ''));

    $sqlH = "
    INSERT INTO {$tDev}
      ({$map['c_id_venta']}, {$map['c_id_usuario']}, {$map['c_total']}, {$map['c_motivo']})
    VALUES (?, ?, ?, ?)
  ";
    $insH = $pdo->prepare($sqlH);
    $insH->execute([$ventaId, $idUsuario, $total, $motivo]);
    $idDev = (int)$pdo->lastInsertId();

    // 4) Insert detalle + devolver stock
    $sqlD = "
    INSERT INTO {$tDet}
      ({$map['d_id_devolucion']}, {$map['d_id_producto']}, {$map['d_cantidad']}, {$map['d_precio']})
    VALUES (?, ?, ?, ?)
  ";
    $insD = $pdo->prepare($sqlD);

    $updStock = $pdo->prepare("
    UPDATE tb_almacen
       SET stock = stock + ?
     WHERE id_producto = ?
  ");

    foreach ($normalized as $n) {
        $insD->execute([$idDev, $n['id_producto'], $n['cantidad'], $n['precio']]);
        $updStock->execute([$n['cantidad'], $n['id_producto']]);
    }

    // 5) Registrar EGRESO en caja (si tu sistema lo hace)
    // Si tu caja usa otra tabla/nombres, lo adapto igual con mapa.
    $qCaja = $pdo->prepare("
    SELECT id_caja
      FROM tb_cajas
     WHERE estado = 'abierta' AND id_usuario = ?
     ORDER BY id_caja DESC
     LIMIT 1
  ");
    $qCaja->execute([$idUsuario]);
    $idCaja = (int)($qCaja->fetchColumn() ?: 0);
    if ($idCaja <= 0) {
        throw new Exception('No tienes caja abierta para registrar el reembolso');
    }

    $mov = $pdo->prepare("
    INSERT INTO tb_caja_movimientos (id_caja, tipo, monto, descripcion, created_at)
    VALUES (?, 'egreso', ?, ?, NOW())
  ");
    $mov->execute([$idCaja, $total, "Devolución venta #{$ventaId} (Dev #{$idDev})"]);

    $pdo->commit();

    json_ok([
        'message' => 'Devolución registrada',
        'id_devolucion' => $idDev,
        'total' => $total,
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log($e->getMessage(), 400);
}
