
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid form token.');
}

$categoryId = filter_input(
    INPUT_POST,
    'category_id',
    FILTER_VALIDATE_INT
);

if (!$categoryId || $categoryId < 1) {
    http_response_code(400);
    exit('Invalid category.');
}

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM products
    WHERE category_id = ?
");

$stmt->execute([$categoryId]);

if ((int) $stmt->fetchColumn() > 0) {
    http_response_code(409);
    exit('This category contains products. Move or delete those products first.');
}

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM categories
    WHERE parent_id = ?
");

$stmt->execute([$categoryId]);

if ((int) $stmt->fetchColumn() > 0) {
    http_response_code(409);
    exit('This category contains subcategories. Move or delete them first.');
}

$stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
$stmt->execute([$categoryId]);

header('Location: categories.php?deleted=1');
exit;
