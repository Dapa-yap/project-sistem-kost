<?php
require_once __DIR__ . '/../config/config.php';
logout_user();
header('Location: ' . url('guest/login.php?logout=1'));
exit;
