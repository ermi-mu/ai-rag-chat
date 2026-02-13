<?php

// Check if vendor/autoload.php exists
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    die('Please run "composer install" in the project root to install dependencies.');
}

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Start Session early to avoid "headers already sent" issues
\App\Core\Auth::startSession();

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();
