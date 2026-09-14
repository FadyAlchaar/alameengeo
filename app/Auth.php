<?php
/**
 * app/Auth.php
 * Local, file-based session authentication.
 * No database tables. No external services.
 */

class Auth
{
    // ═══════════════════════════════════════════════════════════
    //  CONFIGURATION — edit these
    // ═══════════════════════════════════════════════════════════

    // Password hash. Generate a fresh one using _hash-tool.php
    // Default below is for the password: alameen-tracker-2026
    // ⚠️ CHANGE THIS IMMEDIATELY using _hash-tool.php
    private const PASSWORD_HASH = '$2y$10$hvDDWyi73QvH9BGoJIzR8.6nv7ZC/Txz9zpSlI/cnzNwh2SGSaFCy';

    // Session cookie name (obscure by default)
    private const SESSION_NAME  = 'alameen_sid';

    // Idle session timeout in seconds (2 hours)
    private const IDLE_TIMEOUT  = 7200;

    // Absolute session lifetime (7 days)
    private const MAX_LIFETIME  = 604800;

    // Max failed attempts before lockout
    private const MAX_ATTEMPTS  = 5;

    // Lockout duration in seconds (15 min)
    private const LOCKOUT_TIME  = 900;

    // ═══════════════════════════════════════════════════════════

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;

        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        if (empty($_SESSION['auth_ok'])) return false;

        // Idle timeout
        $now  = time();
        $last = $_SESSION['auth_last']    ?? 0;
        $born = $_SESSION['auth_created'] ?? 0;

        if ($last && ($now - $last) > self::IDLE_TIMEOUT) {
            self::logout();
            return false;
        }
        if ($born && ($now - $born) > self::MAX_LIFETIME) {
            self::logout();
            return false;
        }

        $_SESSION['auth_last'] = $now;
        return true;
    }

    public static function require(): void
    {
        if (self::isLoggedIn()) return;

        // If the request is for an API endpoint, return JSON 401
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/api/') !== false) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        // Otherwise redirect to login
        $here = $_SERVER['REQUEST_URI'] ?? '/';
        header('Location: login.php?r=' . urlencode($here));
        exit;
    }

    public static function attempt(string $password): bool
    {
        self::start();

        // Lockout check
        if (!empty($_SESSION['lock_until']) && time() < $_SESSION['lock_until']) {
            return false;
        }

        // Verify
        $ok = password_verify($password, self::PASSWORD_HASH);

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['auth_ok']      = true;
            $_SESSION['auth_created'] = time();
            $_SESSION['auth_last']    = time();
            $_SESSION['auth_fails']   = 0;
            unset($_SESSION['lock_until']);
            return true;
        }

        $_SESSION['auth_fails'] = ($_SESSION['auth_fails'] ?? 0) + 1;
        if ($_SESSION['auth_fails'] >= self::MAX_ATTEMPTS) {
            $_SESSION['lock_until'] = time() + self::LOCKOUT_TIME;
        }
        return false;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function remainingLockSeconds(): int
    {
        self::start();
        if (empty($_SESSION['lock_until'])) return 0;
        $left = $_SESSION['lock_until'] - time();
        return $left > 0 ? $left : 0;
    }

    public static function failedAttempts(): int
    {
        self::start();
        return (int) ($_SESSION['auth_fails'] ?? 0);
    }
}