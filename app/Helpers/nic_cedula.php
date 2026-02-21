<?php
/**
 * Utilidades para Cédula de Nicaragua.
 * Formato común (sin espacios):
 *   MMMDDMMAA####L
 * Donde:
 *   MMM = código municipio (3 dígitos)
 *   DDMMAA = fecha nacimiento (día/mes/año 2 dígitos)
 *   #### = consecutivo (4 dígitos)
 *   L = letra (A-Z) (opcional en algunos sistemas, pero común)
 */

if (!function_exists('nic_cedula_normalize')) {
    function nic_cedula_normalize(string $s): string
    {
        $s = strtoupper(trim($s));
        // quita espacios y guiones
        $s = preg_replace('/[\s\-]+/', '', $s) ?? $s;
        return $s;
    }
}

if (!function_exists('nic_cedula_parse')) {
    /**
     * @return array{ok:bool, municipio?:string, fecha_nacimiento?:string, consecutivo?:string, letra?:string, error?:string}
     */
    function nic_cedula_parse(string $cedula): array
    {
        $c = nic_cedula_normalize($cedula);
        // MMM + DDMMAA + #### + L?
        if (!preg_match('/^(\d{3})(\d{2})(\d{2})(\d{2})(\d{4})([A-Z])?$/', $c, $m)) {
            return ['ok' => false, 'error' => 'Formato de cédula NIC inválido. Ej: 0011401970010N'];
        }

        $mun = $m[1];
        $dd  = (int)$m[2];
        $mm  = (int)$m[3];
        $yy  = (int)$m[4];
        $cons = $m[5];
        $ltr  = $m[6] ?? '';

        // Determinar siglo: si yy > año_actual(2d) => 19yy, si no => 20yy
        $now = new DateTime('now');
        $yyNow = (int)$now->format('y');
        $year = ($yy > $yyNow) ? (1900 + $yy) : (2000 + $yy);

        if (!checkdate($mm, $dd, $year)) {
            return ['ok' => false, 'error' => 'La fecha dentro de la cédula NIC no es válida.'];
        }

        $fecha = sprintf('%04d-%02d-%02d', $year, $mm, $dd);

        return [
            'ok' => true,
            'municipio' => $mun,
            'fecha_nacimiento' => $fecha,
            'consecutivo' => $cons,
            'letra' => $ltr,
        ];
    }
}

if (!function_exists('age_years_from_date')) {
    function age_years_from_date(string $yyyy_mm_dd): int
    {
        try {
            $dob = new DateTime($yyyy_mm_dd);
            $now = new DateTime('now');
            return (int)$now->diff($dob)->y;
        } catch (Throwable $e) {
            return 0;
        }
    }
}
