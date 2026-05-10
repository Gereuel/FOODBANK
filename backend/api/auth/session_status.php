<?php
session_start();
header('Content-Type: application/json');

require_once $_SERVER['DOCUMENT_ROOT'] . '/foodbank/backend/helpers/auth_redirect.php';

$isLoggedIn = isset($_SESSION['Account_ID']);

echo json_encode([
    'logged_in' => $isLoggedIn,
    'dashboard_url' => $isLoggedIn ? auth_dashboard_path($_SESSION['Account_Type'] ?? null) : null,
]);
