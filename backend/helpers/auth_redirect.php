<?php

require_once __DIR__ . '/../config/app.php';

function auth_dashboard_path(?string $accountType): ?string
{
    $redirects = [
        'AA' => app_url('/frontend/views/admin/admin_index.php'),
        'FA' => app_url('/frontend/views/foodbank/index.php'),
        'PA' => app_url('/frontend/views/individual/pa_index.php'),
    ];

    return $redirects[$accountType] ?? null;
}

function send_no_store_headers(): void
{
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
}

function redirect_authenticated_user_to_dashboard(): void
{
    if (!isset($_SESSION['Account_ID'])) {
        return;
    }

    $dashboardPath = auth_dashboard_path($_SESSION['Account_Type'] ?? null);

    if ($dashboardPath !== null) {
        header('Location: ' . $dashboardPath);
        exit();
    }
}

function redirect_to_dashboard_or_login(string $loginPath = '/login.php'): void
{
    redirect_authenticated_user_to_dashboard();

    header('Location: ' . app_url($loginPath));
    exit();
}
