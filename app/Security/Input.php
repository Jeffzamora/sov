<?php
// app/Security/Input.php
declare(strict_types=1);

function _input_raw(string $key): mixed {
  return $_POST[$key] ?? $_GET[$key] ?? null;
}

function input_str(string $key, int $min=0, int $max=255, bool $required=true): string
{
  $v = _input_raw($key);
  if ($v === null || $v === '') {
    if ($required) { throw new RuntimeException("Falta el campo: $key"); }
    return '';
  }
  if (!is_string($v)) { throw new RuntimeException("Formato inválido: $key"); }
  $s = trim($v);
  if (mb_strlen($s) < $min) { throw new RuntimeException("Campo $key muy corto"); }
  if (mb_strlen($s) > $max) { throw new RuntimeException("Campo $key muy largo"); }
  return $s;
}

function input_int(string $key, int $min=PHP_INT_MIN, int $max=PHP_INT_MAX, bool $required=true): int
{
  $v = _input_raw($key);
  if ($v === null || $v === '') {
    if ($required) { throw new RuntimeException("Falta el campo: $key"); }
    return 0;
  }
  if (is_int($v)) $i = $v;
  else {
    if (!is_string($v) && !is_numeric($v)) { throw new RuntimeException("Formato inválido: $key"); }
    $i = (int)$v;
  }
  if ($i < $min || $i > $max) { throw new RuntimeException("Rango inválido: $key"); }
  return $i;
}

function input_email(string $key, bool $required=true): string
{
  $s = input_str($key, 3, 190, $required);
  if ($s === '' && !$required) return '';
  if (!filter_var($s, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException("Email inválido");
  }
  return $s;
}

function input_decimal(string $key, float $min=0, float $max=1e12, bool $required=true): float
{
  $v = _input_raw($key);
  if ($v === null || $v === '') {
    if ($required) { throw new RuntimeException("Falta el campo: $key"); }
    return 0.0;
  }
  if (is_float($v) || is_int($v)) $f = (float)$v;
  else {
    if (!is_string($v)) { throw new RuntimeException("Formato inválido: $key"); }
    $v = str_replace([',',' '], ['.',''], trim($v));
    if (!is_numeric($v)) { throw new RuntimeException("Número inválido: $key"); }
    $f = (float)$v;
  }
  if ($f < $min || $f > $max) { throw new RuntimeException("Rango inválido: $key"); }
  return $f;
}
