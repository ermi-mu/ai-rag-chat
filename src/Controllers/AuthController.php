<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Auth;
use PDO;

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function register(string $username, string $email, string $password, string $role = 'user'): array
    {
        // Basic validation
        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        if (!in_array($role, ['admin', 'user'])) {
             return ['success' => false, 'message' => 'Invalid role specified.'];
        }

        // Check if user exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Username or Email already exists.'];
        }

        // Hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $stmt = $this->db->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hash, $role])) {
            return ['success' => true, 'message' => 'Registration successful! You can now login.'];
        }

        return ['success' => false, 'message' => 'Registration failed due to a database error.'];
    }

    public function login(string $email, string $password): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            Auth::login($user['id'], $user['username'], $user['email'], $user['role']);
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
}
