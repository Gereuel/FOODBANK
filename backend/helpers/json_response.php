<?php

function register_json_exception_handler(string $context = 'Request'): void
{
    set_exception_handler(static function (Throwable $e) use ($context): void {
        error_log($context . ' error: ' . $e->getMessage());

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json');
        }

        echo json_encode([
            'success' => false,
            'message' => 'Server error. Please check the database tables and server logs.',
        ]);
        exit();
    });
}
