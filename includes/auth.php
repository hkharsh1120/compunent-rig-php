
<?php

require_once __DIR__ . '/../config/app.php';

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /compunent-rig/auth/login.php');
        exit;
    }
}

function currentUserId(): ?int
{
    return isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;
}

function requireAdmin(PDO $pdo): void
{
    requireLogin();

    $stmt = $pdo->prepare("
        SELECT role, status
        FROM users
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->execute([currentUserId()]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        $_SESSION = [];
        session_destroy();

        header('Location: /compunent-rig/auth/login.php');
        exit;
    }

    if ($user['role'] !== 'admin') {
        http_response_code(403);
        exit('Access denied. Administrator privileges required.');
    }
}
