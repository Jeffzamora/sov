<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../layout/sesion.php';

if (function_exists('require_perm')) require_perm($pdo, 'reportes.ver', $URL . '/');
require_once __DIR__ . '/../layout/parte1.php';

function h($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/**
 * Reporte: Control anual de exámenes
 * Tipos:
 *  - vencidos    : >= 365 días desde último examen
 *  - proximos    : a X días de cumplir 1 año (default 30)
 *  - sin_examen  : nunca se han examinado
 *  - todos
 */

$tipo   = strtolower($_GET['tipo'] ?? 'proximos');
$q      = trim($_GET['q'] ?? '');
$window = (int)($_GET['window'] ?? 30);
if ($window < 1) $window = 30;
if ($window > 180) $window = 180;

$tiposValidos = ['vencidos', 'proximos', 'sin_examen', 'todos'];
if (!in_array($tipo, $tiposValidos, true)) {
    $tipo = 'proximos';
}

$params = [];
$where  = [];

/* Buscador */
if ($q !== '') {
    $where[] = "(c.nombre LIKE :q OR c.apellido LIKE :q OR c.numero_documento LIKE :q)";
    $params[':q'] = "%$q%";
}

/* Subconsulta: último examen por cliente */
$sub = "
    SELECT id_cliente, MAX(fecha_examen) AS ultima_fecha_examen
    FROM tb_examenes_optometricos
    GROUP BY id_cliente
";

/* Filtro por tipo */
switch ($tipo) {
    case 'vencidos':
        $where[] = "ex.ultima_fecha_examen IS NOT NULL
                    AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen) >= 365";
        break;

    case 'proximos':
        $where[] = "ex.ultima_fecha_examen IS NOT NULL
                    AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen)
                        BETWEEN (365 - :window) AND 364";
        $params[':window'] = $window;
        break;

    case 'sin_examen':
        $where[] = "ex.ultima_fecha_examen IS NULL";
        break;

    case 'todos':
    default:
        // sin filtro adicional
        break;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT
        c.id_cliente,
        c.nombre,
        c.apellido,
        c.numero_documento,
        ex.ultima_fecha_examen,
        CASE
            WHEN ex.ultima_fecha_examen IS NULL THEN NULL
            ELSE DATEDIFF(CURDATE(), ex.ultima_fecha_examen)
        END AS dias_desde_ultimo,
        CASE
            WHEN ex.ultima_fecha_examen IS NULL THEN NULL
            ELSE DATE_ADD(ex.ultima_fecha_examen, INTERVAL 365 DAY)
        END AS proximo_control,
        CASE
            WHEN ex.ultima_fecha_examen IS NULL THEN NULL
            ELSE DATEDIFF(DATE_ADD(ex.ultima_fecha_examen, INTERVAL 365 DAY), CURDATE())
        END AS dias_para_vencer
    FROM tb_clientes c
    LEFT JOIN ($sub) ex ON ex.id_cliente = c.id_cliente
    $whereSql
    ORDER BY
        ex.ultima_fecha_examen IS NULL DESC,
        ex.ultima_fecha_examen ASC,
        c.nombre ASC,
        c.apellido ASC
    LIMIT 1000
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

/* KPIs */
$kpi = ['vencidos' => 0, 'proximos' => 0, 'sin_examen' => 0];
try {
    $k = $pdo->prepare("
        SELECT
            SUM(ex.ultima_fecha_examen IS NOT NULL
                AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen) >= 365) AS vencidos,
            SUM(ex.ultima_fecha_examen IS NOT NULL
                AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen)
                    BETWEEN (365 - :w) AND 364) AS proximos,
            SUM(ex.ultima_fecha_examen IS NULL) AS sin_examen
        FROM tb_clientes c
        LEFT JOIN (
            SELECT id_cliente, MAX(fecha_examen) AS ultima_fecha_examen
            FROM tb_examenes_optometricos
            GROUP BY id_cliente
        ) ex ON ex.id_cliente = c.id_cliente
    ");
    $k->execute([':w' => $window]);
    $kpi = $k->fetch(PDO::FETCH_ASSOC) ?: $kpi;
} catch (Throwable $e) {
}
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="m-0">Reporte: Control anual de exámenes</h1>
            <div class="text-muted">
                <span class="badge badge-danger">Vencidos: <?php echo (int)$kpi['vencidos']; ?></span>
                <span class="badge badge-warning ml-1">Próximos (<?php echo $window; ?> días): <?php echo (int)$kpi['proximos']; ?></span>
                <span class="badge badge-secondary ml-1">Sin examen: <?php echo (int)$kpi['sin_examen']; ?></span>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <form method="get" class="card card-body mb-3">
                <div class="form-row">
                    <div class="col-md-3">
                        <label>Tipo</label>
                        <select name="tipo" class="form-control">
                            <option value="proximos" <?php if ($tipo === 'proximos') echo 'selected'; ?>>Próximos</option>
                            <option value="vencidos" <?php if ($tipo === 'vencidos') echo 'selected'; ?>>Vencidos</option>
                            <option value="sin_examen" <?php if ($tipo === 'sin_examen') echo 'selected'; ?>>Sin examen</option>
                            <option value="todos" <?php if ($tipo === 'todos') echo 'selected'; ?>>Todos</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label>Ventana (días)</label>
                        <input type="number" name="window" class="form-control"
                            value="<?php echo (int)$window; ?>">
                    </div>

                    <div class="col-md-4">
                        <label>Buscar</label>
                        <input type="text" name="q" class="form-control"
                            value="<?php echo h($q); ?>"
                            placeholder="Nombre, apellido o documento">
                    </div>

                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                    </div>
                </div>
            </form>

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-sm table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Documento</th>
                                <th>Último examen</th>
                                <th class="text-right">Días</th>
                                <th>Próximo control</th>
                                <th class="text-right">Faltan</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Sin resultados</td>
                                </tr>
                            <?php endif; ?>

                            <?php foreach ($rows as $r): ?>
                                <?php
                                $badge = '';
                                if ($r['ultima_fecha_examen'] === null) {
                                    $badge = '<span class="badge badge-secondary">Sin examen</span>';
                                } elseif ($r['dias_desde_ultimo'] >= 365) {
                                    $badge = '<span class="badge badge-danger">Vencido</span>';
                                } elseif ($r['dias_para_vencer'] <= $window) {
                                    $badge = '<span class="badge badge-warning">Próximo</span>';
                                }
                                ?>
                                <tr>
                                    <td><?php echo (int)$r['id_cliente']; ?></td>
                                    <td><?php echo h($r['nombre'] . ' ' . $r['apellido']); ?> <?php echo $badge; ?></td>
                                    <td><?php echo h($r['numero_documento']); ?></td>
                                    <td><?php echo h($r['ultima_fecha_examen']); ?></td>
                                    <td class="text-right"><?php echo h($r['dias_desde_ultimo']); ?></td>
                                    <td><?php echo h($r['proximo_control']); ?></td>
                                    <td class="text-right"><?php echo h($r['dias_para_vencer']); ?></td>
                                    <td>
                                        <a class="btn btn-xs btn-outline-primary"
                                            href="<?php echo $URL; ?>/clientes/show.php?id=<?php echo (int)$r['id_cliente']; ?>&tab=examenes">
                                            Expediente
                                        </a>
                                        <a class="btn btn-xs btn-outline-success"
                                            href="<?php echo $URL; ?>/clientes/examenes/new.php?id=<?php echo (int)$r['id_cliente']; ?>">
                                            Nuevo examen
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
</div>

<?php require_once __DIR__ . '/../layout/parte2.php'; ?>