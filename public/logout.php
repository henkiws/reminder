<?php
require_once '../config/database.php';
require_once '../classes/AuthManager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new AuthManager($db);

// Logout user
$auth->logout();

// Redirect to login page
header('Location: login.php?message=logged_out');
exit;
?>