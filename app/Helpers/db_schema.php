<?php
// Helpers para inspección de esquema (compatibles con PDO real y sin depender de SHOW ... LIKE con parámetros)

if (!function_exists('sov_table_exists')) {
function sov_table_exists(PDO $pdo, string $table): bool {
  $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t");
  $q->execute([':t' => $table]);
  return (int)$q->fetchColumn() > 0;
}
}

if (!function_exists('sov_column_exists')) {
function sov_column_exists(PDO $pdo, string $table, string $column): bool {
  $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c");
  $q->execute([':t' => $table, ':c' => $column]);
  return (int)$q->fetchColumn() > 0;
}
}

if (!function_exists('sov_pick_existing_table')) {
function sov_pick_existing_table(PDO $pdo, array $candidates): ?string {
  foreach ($candidates as $t) {
    if (sov_table_exists($pdo, $t)) return $t;
  }
  return null;
}
}

if (!function_exists('sov_pick_existing_column')) {
function sov_pick_existing_column(PDO $pdo, string $table, array $candidates): ?string {
  foreach ($candidates as $c) {
    if (sov_column_exists($pdo, $table, $c)) return $c;
  }
  return null;
}
}
