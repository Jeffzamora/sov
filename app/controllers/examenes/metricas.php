<?php

declare(strict_types=1);

/**
 * Métricas para dashboard - Exámenes
 * - Total exámenes
 * - Exámenes hoy
 * - Exámenes últimos 30 días
 * - Clientes vencidos/proximos (control anual)
 * - Últimos 10 exámenes con nombre del cliente
 */

if (!isset($pdo) || !($pdo instanceof PDO)) {
    return; // fail-safe para no romper dashboard
}

$ex_kpi = [
    'total' => 0,
    'hoy' => 0,
    'ult_30' => 0,
    'vencidos' => 0,
    'proximos' => 0,
];

$ex_ultimos = [];

try {
    // KPI básicos
    $q = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM tb_examenes_optometricos) AS total,
      (SELECT COUNT(*) FROM tb_examenes_optometricos WHERE fecha_examen = CURDATE()) AS hoy,
      (SELECT COUNT(*) FROM tb_examenes_optometricos WHERE fecha_examen >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS ult_30
  ");
    $ex_kpi = $q->fetch(PDO::FETCH_ASSOC) ?: $ex_kpi;

    // Control anual (vencidos / próximos)
    $window_days = 30; // ajusta si quieres
    $st = $pdo->prepare("
    SELECT
      SUM(ex.ultima_fecha_examen IS NOT NULL AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen) >= 365) AS vencidos,
      SUM(ex.ultima_fecha_examen IS NOT NULL AND DATEDIFF(CURDATE(), ex.ultima_fecha_examen) BETWEEN (365 - :w) AND 364) AS proximos
    FROM tb_clientes c
    LEFT JOIN (
      SELECT id_cliente, MAX(fecha_examen) AS ultima_fecha_examen
      FROM tb_examenes_optometricos
      GROUP BY id_cliente
    ) ex ON ex.id_cliente = c.id_cliente
  ");
    $st->execute([':w' => $window_days]);
    $cx = $st->fetch(PDO::FETCH_ASSOC) ?: ['vencidos' => 0, 'proximos' => 0];

    $ex_kpi['vencidos'] = (int)($cx['vencidos'] ?? 0);
    $ex_kpi['proximos'] = (int)($cx['proximos'] ?? 0);

    // Últimos 10 exámenes
    $st2 = $pdo->query("
    SELECT
      e.id_examen,
      e.fecha_examen,
      e.id_cliente,
      c.nombre,
      c.apellido,
      c.numero_documento
    FROM tb_examenes_optometricos e
    INNER JOIN tb_clientes c ON c.id_cliente = e.id_cliente
    ORDER BY e.fecha_examen DESC, e.id_examen DESC
    LIMIT 10
  ");
    $ex_ultimos = $st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    // no romper dashboard
}
