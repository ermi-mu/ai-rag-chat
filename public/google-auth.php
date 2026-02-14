<?php
require_once 'init.php';

$clientId = trim($_ENV['GOOGLE_CLIENT_ID'] ?? '');
$clientSecret = trim($_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
$redirectUri = trim($_ENV['GOOGLE_REDIRECT_URI'] ?? '');

if (empty($clientId) || empty($clientSecret) || empty($redirectUri)) {
    header('Location: login.php?error=' . urlencode('Google Login is not configured. Please add GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, and GOOGLE_REDIRECT_URI to your .env file.'));
    exit;
}

$client = new Google\Client();
$client->setClientId($clientId);
$client->setClientSecret($clientSecret);
$client->setRedirectUri($redirectUri);
$client->addScope("email");
$client->addScope("profile");

// Clear any existing OAuth sessions
if (isset($_SESSION['access_token'])) {
    unset($_SESSION['access_token']);
}

$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
