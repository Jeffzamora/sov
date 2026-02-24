<?php
declare(strict_types=1);

/**
 * Settings (KV) para SOV.
 *
 * Tabla esperada: tb_settings
 * - `key` VARCHAR(80) PK
 * - `value` TEXT
 * - `type` ENUM('string','int','bool','json')
 * - `updated_at` DATETIME
 * - `updated_by` INT NULL
 *
 * Diseño:
 * - Lectura con cache en sesión (por request / usuario).
 * - No rompe si la tabla no existe.
 */

if (!function_exists('settings_table_ready')) {
  function settings_table_ready(PDO $pdo): bool
  {
    try {
      return function_exists('sov_table_exists') ? sov_table_exists($pdo, 'tb_settings') : false;
    } catch (Throwable $e) {
      return false;
    }
  }
}

if (!function_exists('settings_cache_get')) {
  function settings_cache_get(): array
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      return [];
    }
    $c = $_SESSION['_settings_cache'] ?? [];
    return is_array($c) ? $c : [];
  }
}

if (!function_exists('settings_cache_put')) {
  function settings_cache_put(string $key, $val): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    if (!isset($_SESSION['_settings_cache']) || !is_array($_SESSION['_settings_cache'])) {
      $_SESSION['_settings_cache'] = [];
    }
    $_SESSION['_settings_cache'][$key] = $val;
  }
}

if (!function_exists('settings_cache_clear')) {
  function settings_cache_clear(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) return;
    unset($_SESSION['_settings_cache']);
  }
}

if (!function_exists('setting')) {
  /**
   * Lee un setting.
   * - $default se retorna si no existe la tabla o la key.
   */
  function setting(string $key, $default = null)
  {
    global $pdo;
    if (!($pdo instanceof PDO)) return $default;

    // cache
    $cache = settings_cache_get();
    if (array_key_exists($key, $cache)) {
      return $cache[$key];
    }

    if (!settings_table_ready($pdo)) {
      settings_cache_put($key, $default);
      return $default;
    }

    try {
      $st = $pdo->prepare("SELECT `value`,`type` FROM tb_settings WHERE `key` = ? LIMIT 1");
      $st->execute([$key]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row) {
        settings_cache_put($key, $default);
        return $default;
      }
      $type = (string)($row['type'] ?? 'string');
      $raw  = (string)($row['value'] ?? '');

      switch ($type) {
        case 'int':
          $val = (int)$raw;
          break;
        case 'bool':
          $val = ($raw === '1' || strtolower($raw) === 'true');
          break;
        case 'json':
          $tmp = json_decode($raw, true);
          $val = (json_last_error() === JSON_ERROR_NONE) ? $tmp : $default;
          break;
        default:
          $val = $raw;
      }

      settings_cache_put($key, $val);
      return $val;
    } catch (Throwable $e) {
      settings_cache_put($key, $default);
      return $default;
    }
  }
}

if (!function_exists('setting_set')) {
  /**
   * Guarda/actualiza un setting y limpia cache.
   */
  function setting_set(PDO $pdo, string $key, $value, string $type = 'string', ?int $updatedBy = null): void
  {
    if (!settings_table_ready($pdo)) {
      throw new RuntimeException('La tabla tb_settings no existe. Ejecuta el script SQL de migración.');
    }

    $type = in_array($type, ['string', 'int', 'bool', 'json'], true) ? $type : 'string';

    if ($type === 'json') {
      $store = json_encode($value, JSON_UNESCAPED_UNICODE);
      if ($store === false) $store = 'null';
    } elseif ($type === 'bool') {
      $store = ($value ? '1' : '0');
    } else {
      $store = (string)$value;
    }

    $sql = "INSERT INTO tb_settings (`key`,`value`,`type`,`updated_at`,`updated_by`)
            VALUES (:k,:v,:t,NOW(),:u)
            ON DUPLICATE KEY UPDATE `value`=VALUES(`value`), `type`=VALUES(`type`), `updated_at`=NOW(), `updated_by`=VALUES(`updated_by`)";
    $st = $pdo->prepare($sql);
    $st->execute([
      ':k' => $key,
      ':v' => $store,
      ':t' => $type,
      ':u' => $updatedBy,
    ]);

    settings_cache_clear();
  }
}

if (!function_exists('settings_all')) {
  /**
   * Lista settings (opcional por prefijo).
   */
  function settings_all(PDO $pdo, string $prefix = ''): array
  {
    if (!settings_table_ready($pdo)) return [];

    $prefix = trim($prefix);
    $sql = "SELECT `key`,`value`,`type` FROM tb_settings";
    $args = [];
    if ($prefix !== '') {
      $sql .= " WHERE `key` LIKE ?";
      $args[] = $prefix . '%';
    }
    $sql .= " ORDER BY `key` ASC";

    $st = $pdo->prepare($sql);
    $st->execute($args);

    $out = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
      $k = (string)($r['key'] ?? '');
      if ($k === '') continue;
      $out[$k] = setting($k, null);
    }
    return $out;
  }
}
