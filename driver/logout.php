<?php
session_start();
require_once __DIR__ . '/config.php';
unset($_SESSION[DRIVER_SESSION_KEY], $_SESSION['driver_time']);
header('Location: login.php');
exit;
