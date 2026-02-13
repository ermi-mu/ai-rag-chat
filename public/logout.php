<?php
require_once 'init.php';
use App\Core\Auth;

Auth::logout();
header('Location: login.php');
exit;
