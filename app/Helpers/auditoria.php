<?php
// app/Helpers/auditoria.php
// Auditoría centralizada para el sistema.
//
// IMPORTANTE:
// - Tu BD (tb_auditoria) usa TRIGGERS para capturar INSERT/UPDATE/DELETE con JSON (antes/despues)
//   y variables de sesión MySQL: @app_user_id, @app_user_email, @app_ip, @app_ua.
// - Además, este helper permite registrar eventos de aplicación (LOGIN/LOGOUT, etc.)
//   en la misma tabla de auditoría sin romper el ENUM (accion).

declare(strict_types=1);

if (!function_exists('sov_current_user_id')) {
    function sov_current_user_id(): ?int
    {
        if (!empty($_SESSION['sesion_id_usuario'])) return (int)$_SESSION['sesion_id_usuario'];
        if (!empty($_SESSION['usuario']['id_usuario'])) return (int)$_SESSION['usuario']['id_usuario'];
        if (!empty($_SESSION['id_usuario'])) return (int)$_SESSION['id_usuario'];
        return null;
    }
}

if (!function_exists('sov_current_user_email')) {
    function sov_current_user_email(): ?string
    {
        if (!empty($_SESSION['sesion_email'])) return (string)$_SESSION['sesion_email'];
        if (!empty($_SESSION['usuario']['email'])) return (string)$_SESSION['usuario']['email'];
        if (!empty($_SESSION['email'])) return (string)$_SESSION['email'];
        return null;
    }
}

if (!function_exists('sov_client_ip')) {
    function sov_client_ip(): ?string
    {
        // En proxy / load balancer, X-Forwarded-For puede traer lista
        $ip = $_SERVER['HTTP_CLIENT_IP']
            ?? $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;

        if (is_string($ip) && strpos($ip, ',') !== false) {
            $ip = trim(explode(',', $ip)[0]);
        }
        $ip = is_string($ip) ? trim($ip) : null;
        return $ip !== '' ? $ip : null;
    }
}

if (!function_exists('sov_user_agent')) {
    function sov_user_agent(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';
        if (!is_string($ua) || trim($ua) === '') return 'UNKNOWN';
        return $ua;
    }
}

if (!function_exists('auditoria_log')) {
    /**
     * Registra un evento en tb_auditoria.
     *
     * Nota:
     * - tb_auditoria.accion es ENUM('INSERT','UPDATE','DELETE').
     * - Si $accion viene con valores tipo LOGIN/LOGOUT/CREAR/etc.
     *   se normaliza a 'UPDATE' para cumplir el ENUM, y el evento real
     *   queda dentro del JSON 'despues'.
     */
    function auditoria_log(
        PDO $pdo,
        string $accion,
        string $tabla,
        ?int $registro_id = null,
        ?string $descripcion = null
    ): void {
        try {
            if (function_exists('ensure_session')) {
                ensure_session();
            } elseif (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }

            $uid = sov_current_user_id();
            $uemail = sov_current_user_email();
            $ip = sov_client_ip();
            $ua = substr(sov_user_agent(), 0, 255);

            $accion = strtoupper(trim($accion));
            $accion_db = in_array($accion, ['INSERT', 'UPDATE', 'DELETE'], true) ? $accion : 'UPDATE';

            $payload = [
                'evento' => $accion,
                'descripcion' => $descripcion,
                'registro_id' => $registro_id,
                'ts' => date('c'),
            ];

            $sql = "
                INSERT INTO tb_auditoria
                    (tabla, accion, pk, usuario_id, usuario_email, ip, user_agent, antes, despues)
                VALUES
                    (:tabla, :accion, :pk, :usuario_id, :usuario_email, :ip, :user_agent, :antes, :despues)
            ";

            $st = $pdo->prepare($sql);
            $st->execute([
                ':tabla' => $tabla,
                ':accion' => $accion_db,
                ':pk' => $registro_id !== null ? (string)$registro_id : null,
                ':usuario_id' => $uid,
                ':usuario_email' => $uemail,
                ':ip' => $ip,
                ':user_agent' => $ua,
                ':antes' => null,
                ':despues' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (Throwable $e) {
            // Nunca romper el flujo del sistema por auditoría
            error_log('Auditoria error: ' . $e->getMessage());
        }
    }
}
