<?php
// app/Helpers/auditoria.php
// Auditoría centralizada para el sistema.

declare(strict_types=1);

if (!function_exists('sov_current_user_id')) {
    function sov_current_user_id(): ?int
    {
        // Convención actual del sistema
        if (!empty($_SESSION['sesion_id_usuario'])) {
            return (int)$_SESSION['sesion_id_usuario'];
        }
        // Fallbacks
        if (!empty($_SESSION['usuario']['id_usuario'])) {
            return (int)$_SESSION['usuario']['id_usuario'];
        }
        if (!empty($_SESSION['id_usuario'])) {
            return (int)$_SESSION['id_usuario'];
        }
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
     * Inserta en tb_auditoria.
     * Recomendado: columnas (usuario_id, accion, tabla, registro_id, descripcion, ip, user_agent, fyh_creacion)
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
            $ip = sov_client_ip();
            $ua = sov_user_agent();

            $sql = "
                INSERT INTO tb_auditoria
                (usuario_id, accion, tabla, registro_id, descripcion, ip, user_agent, fyh_creacion)
                VALUES
                (:usuario_id, :accion, :tabla, :registro_id, :descripcion, :ip, :user_agent, NOW())
            ";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':usuario_id' => $uid,
                ':accion' => $accion,
                ':tabla' => $tabla,
                ':registro_id' => $registro_id,
                ':descripcion' => $descripcion,
                ':ip' => $ip,
                ':user_agent' => $ua,
            ]);
        } catch (Throwable $e) {
            // Nunca romper el flujo del sistema por auditoría
            error_log('Auditoria error: ' . $e->getMessage());
        }
    }
}
