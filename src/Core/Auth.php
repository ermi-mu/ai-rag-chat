<?php

namespace App\Core;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(int $userId, string $username, string $email, string $role): void
    {
        self::startSession();
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        session_regenerate_id(true); // Security measure
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        session_destroy();
    }

    public static function check(): bool
    {
        self::startSession();
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        self::startSession();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function user(): ?array
    {
        self::startSession();
        if (self::check()) {
            return [
                'id' => $_SESSION['user_id'] ?? null,
                'username' => $_SESSION['username'] ?? 'Guest',
                'email' => $_SESSION['email'] ?? '',
                'role' => $_SESSION['role'] ?? 'user'
            ];
        }
        return null;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            die('Access Denied: Admin privileges required.');
        }
    }
    
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}
