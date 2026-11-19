<?php
declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

function requireLogin(): void {
    if (empty($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}
function currentUser(): array {
    return $_SESSION['user'] ?? [];
}
function requireRole(array $roles): void {
    requireLogin();
    if (!in_array($_SESSION['user']['role'] ?? '', $roles, true)) {
        http_response_code(403);
        exit('You do not have permission to access this section.');
    }
}
