<?php
// Login and session handling. auth.php calls Auth::requireLogin() at the top of every protected page.
class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            // The session cookie cannot be read by JavaScript, and is only sent over HTTPS when the site uses it.
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            ]);
            session_start();
        }
    }

    public static function isLoggedIn(): bool
    {
        self::start();
        return !empty($_SESSION['account_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit;
        }
    }

    public static function login(PDO $pdo, string $username, string $password): bool
    {
        $admin = (new AdminRepository($pdo))->findByUsername(trim($username));
        if ($admin === null || !$admin->verifyPassword($password)) {
            return false;
        }
        self::start();
        session_regenerate_id(true);
        $_SESSION['account_id'] = $admin->getId();
        $_SESSION['username']   = $admin->getUsername();
        return true;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
