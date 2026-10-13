<?php
require_once __DIR__ . '/../config/config.php';
$_SESSION = [];
session_destroy();
redirect('guest/login.php?logout=1');
