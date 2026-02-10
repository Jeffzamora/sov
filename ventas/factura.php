<?php
require_once __DIR__ . '/../app/config.php';

function h($v)
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

// Datos de la óptica / empresa
$optica = optica_info();
$empresa = $optica['nombre'] ?? 'OPTICA';
$ruc = $optica['ruc'] ?? '000000000';
$direccion = $optica['direccion'] ?? '';
$telefono = $optica['telefono'] ?? '';
$email = $optica['email'] ?? '';
$logo = $optica['logo'] ?? '';

// Venta
$id = (int)($_GET['id'] ?? 0);

$q = $pdo->prepare("
  SELECT v.*, c.nombre, c.apellido, c.numero_documento
  FROM tb_ventas v
  INNER JOIN tb_clientes c ON c.id_cliente = v.id_cliente
  WHERE v.id_venta = ?
  LIMIT 1
");
$q->execute([$id]);
$v = $q->fetch(PDO::FETCH_ASSOC);

$det = $pdo->prepare("
  SELECT d.*, a.nombre AS producto
  FROM tb_ventas_detalle d
  INNER JOIN tb_almacen a ON a.id_producto = d.id_producto
  WHERE d.id_venta = ?
");
$det->execute([$id]);

if (!$v) {
    die('Venta no encontrada');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $URL; ?>/public/images/optica/icon_bajo.png">
    <title>Factura #<?= h($v['id_venta']) ?></title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #000;
        }

        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header img {
            max-height: 80px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
        }

        .info,
        .cliente {
            margin-top: 15px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            border: 1px solid #000;
            padding: 6px;
        }

        .table th {
            background: #f2f2f2;
        }

        .totales {
            margin-top: 15px;
            width: 40%;
            float: right;
        }

        .totales td {
            padding: 5px;
        }

        .footer {
            margin-top: 80px;
            text-align: center;
            font-size: 11px;
        }
    </style>
</head>

<body>

    <div class="header">
        <div>
            <?php if ($logo): ?>
                <img src="<?= h($logo) ?>">
            <?php endif; ?>
            <div class="titulo"><?= h($empresa) ?></div>
            <div>RUC: <?= h($ruc) ?></div>
            <div><?= h($direccion) ?></div>
            <div>Tel: <?= h($telefono) ?></div>
            <div><?= h($email) ?></div>
        </div>
        <div>
            <strong>FACTURA</strong><br>
            Nº <?= h($v['id_venta']) ?><br>
            Fecha: <?= date('d/m/Y H:i', strtotime($v['fyh_creacion'])) ?>
        </div>
    </div>

    <div class="cliente">
        <strong>Cliente:</strong><br>
        <?= h($v['nombre'] . ' ' . $v['apellido']) ?><br>
        Documento: <?= h($v['numero_documento']) ?>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>Precio</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php $total = 0; ?>
            <?php foreach ($det as $d):
                $sub = $d['cantidad'] * $d['precio_unitario'];
                $total += $sub;
            ?>
                <tr>
                    <td><?= h($d['producto']) ?></td>
                    <td align="center"><?= h($d['cantidad']) ?></td>
                    <td align="right">C$ <?= number_format($d['precio_unitario'], 2) ?></td>
                    <td align="right">C$ <?= number_format($sub, 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td><strong>Total</strong></td>
            <td align="right"><strong>C$ <?= number_format($total, 2) ?></strong></td>
        </tr>
    </table>

    <div style="clear:both"></div>

    <div class="footer">
        Gracias por su compra<br>
        Documento válido como factura
    </div>

</body>

</html>