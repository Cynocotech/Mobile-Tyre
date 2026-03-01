<?php
session_start();
unset($_SESSION['admin_ok'], $_SESSION['admin_time']);
header('Location: login.php');
exit;
