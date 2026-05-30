<?php
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . '/../includes/required.php');
require_once(__DIR__ . '/../includes/custom_functions.inc.php');
gdrcd_connect();
echo json_encode(['test' => 'includes ok', 'login' => $_SESSION['login'] ?? 'no session']);
