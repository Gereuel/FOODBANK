<?php

if (!defined('APP_BASE_PATH')) {
    $configuredBasePath = getenv('FOODBANK_BASE_PATH');

    if ($configuredBasePath === false) {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $configuredBasePath = strpos($scriptName, '/foodbank/') === 0 || $scriptName === '/foodbank'
            ? '/foodbank'
            : '';
    }

    $configuredBasePath = '/' . trim((string) $configuredBasePath, '/');
    define('APP_BASE_PATH', $configuredBasePath === '/' ? '' : $configuredBasePath);
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__, 2));
}

function app_url(string $path = ''): string
{
    $path = '/' . ltrim($path, '/');
    return APP_BASE_PATH . ($path === '/' ? '' : $path);
}

function app_absolute_url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    return $scheme . '://' . $host . app_url($path);
}

function app_path(string $path = ''): string
{
    return APP_ROOT . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
}

function app_redirect(string $path): void
{
    header('Location: ' . app_url($path));
    exit();
}

function app_rewrite_public_paths(string $content): string
{
    if (APP_BASE_PATH === '/foodbank') {
        return $content;
    }

    return str_replace('/foodbank/', app_url('/') . '/', $content);
}

if (!defined('APP_PATH_REWRITE_STARTED')) {
    define('APP_PATH_REWRITE_STARTED', true);
    ob_start('app_rewrite_public_paths');

    header_register_callback(function (): void {
        if (APP_BASE_PATH === '/foodbank') {
            return;
        }

        foreach (headers_list() as $header) {
            if (stripos($header, 'Location: /foodbank/') === 0) {
                $location = trim(substr($header, strlen('Location:')));
                header_remove('Location');
                header('Location: ' . app_url(substr($location, strlen('/foodbank'))));
                break;
            }
        }
    });
}
