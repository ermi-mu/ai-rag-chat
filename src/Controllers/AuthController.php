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

    public function requestReset(string $email): array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            // For security, don't confirm if email exists, but here we will be helpful for the simulation
            return ['success' => false, 'message' => 'Email address not found.'];
        }

        // Generate a 6-digit numeric token
        $token = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
        $stmt->execute([$token, $user['id']]);

        // Send actual email
        $mailService = new \App\Services\MailService();
        $mailSent = $mailService->sendPasswordReset($email, $token);

        if ($mailSent) {
            return [
                'success' => true, 
                'message' => 'A password reset token has been sent to your email address.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to send reset email. Please try again later or contact support.'
            ];
        }
    }

    public function resetPassword(string $email, string $token, string $newPassword): array
    {
        $token = trim($token);
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters.'];
        }

        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$email, $token]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Invalid or expired reset token.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $user['id']]);

        return ['success' => true, 'message' => 'Your password has been updated!'];
    }
}
