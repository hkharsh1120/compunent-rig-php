
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

$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);

if (!$productId || $productId < 1) {
    http_response_code(400);
    exit('Invalid product.');
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT id
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    $stmt->execute([$productId]);

    if (!$stmt->fetch()) {
        throw new RuntimeException('Product not found.');
    }

    $stmt = $pdo->prepare("
        SELECT image_path
        FROM product_images
        WHERE product_id = ?
    ");

    $stmt->execute([$productId]);
    $images = $stmt->fetchAll();

    $pdo->prepare("
        DELETE FROM product_images
        WHERE product_id = ?
    ")->execute([$productId]);

    $pdo->prepare("
        DELETE FROM products
        WHERE id = ?
    ")->execute([$productId]);

    $pdo->commit();

    // Remove image files after successful database deletion.
    foreach ($images as $image) {

        $relativePath = $image['image_path'];

        if (
            strpos($relativePath, 'uploads/products/') === 0
            && basename($relativePath) === $relativePath
        ) {
            $file = __DIR__ . '/../' . $relativePath;

            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    header('Location: products.php?deleted=1');
    exit;

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('Product deletion error: ' . $e->getMessage());

    http_response_code(409);
    exit('Product could not be deleted. It may be referenced by another record.');
}
