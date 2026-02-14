<?php
require_once 'init.php';

use App\Core\Database;
use App\Core\Auth;

$clientId = trim($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$clientSecret = trim($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$redirectUri = trim($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
    header('Location: login.php?error=' . urlencode('Google Login configuration missing.'));
    exit;
}

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);

if (!isset($_GET['code'])) {
    header('Location: login.php?error=no_auth_code');
    exit;
}

try {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    if (isset($token['error'])) {
        throw new \Exception($token['error_description'] ?? 'Failed to exchange auth code');
    }
    
    $client->setAccessToken($token);
    $googleService = new Google\Service\Oauth2($client);
    $userinfo = $googleService->userinfo->get();

    $email = $userinfo->email;
    $google_id = $userinfo->id;
    $name = $userinfo->name;

    $db = Database::getConnection();

    // Check if user already exists by google_id
    $stmt = $db->prepare("SELECT * FROM users WHERE google_id = ?");
    $stmt->execute([$google_id]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);

    if (!$user) {
        // Check if user exists by email (link accounts if they were previously registered via form)
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($user) {
            // Link existing account with Google
            $stmt = $db->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $stmt->execute([$google_id, $user['id']]);
        } else {
            // Create new Google user
            // We use a random password since they will login via Google
            $random_password = bin2hex(random_bytes(16));
            $password_hash = password_hash($random_password, PASSWORD_DEFAULT);
            $username = strtolower(str_replace(' ', '_', $name)) . '_' . substr($google_id, -4);
            
            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, google_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password_hash, $google_id]);
            
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$db->lastInsertId()]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        }
    }

    // Set Session using Auth helper
    Auth::login($user['id'], $user['username'], $user['email'], $user['role'] ?? 'user');

    header('Location: index.php');
    exit;

} catch (\Exception $e) {
    header('Location: login.php?error=' . urlencode($e->getMessage()));
    exit;
}
