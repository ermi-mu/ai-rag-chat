<?php
require_once 'init.php';

use App\Controllers\ChatController;

$controller = new ChatController();
$controller->handleRequest();
