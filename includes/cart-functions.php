
<?php

require_once __DIR__ . '/../config/app.php';

function getCartItems(PDO $pdo): array
{
    $cart = $_SESSION['cart'] ?? [];

    if (!is_array($cart) || empty($cart)) {
        return [];
    }

    $ids = [];

    foreach ($cart as $id => $quantity) {
        if (
            filter_var($id, FILTER_VALIDATE_INT) !== false
            && (int) $id > 0
            && is_int($quantity)
            && $quantity > 0
        ) {
            $ids[] = (int) $id;
        }
    }

    $ids = array_values(array_unique($ids));

    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.price,
            p.stock_quantity,
            p.status,
            (
                SELECT pi.image_path
                FROM product_images AS pi
                WHERE pi.product_id = p.id
                  AND pi.is_primary = 1
                ORDER BY pi.id ASC
                LIMIT 1
            ) AS image_path
        FROM products AS p
        WHERE p.id IN ($placeholders)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($ids);

    $products = $stmt->fetchAll();
    $items = [];

    foreach ($products as $product) {
        $id = (int) $product['id'];
        $quantity = $_SESSION['cart'][$id] ?? 0;

        if (!is_int($quantity) || $quantity < 1) {
            continue;
        }

        $product['quantity'] = $quantity;
        $product['line_total'] = (float) $product['price'] * $quantity;

        $items[] = $product;
    }

    return $items;
}

function cartSubtotal(array $items): float
{
    $subtotal = 0.0;

    foreach ($items as $item) {
        $subtotal += (float) $item['line_total'];
    }

    return round($subtotal, 2);
}
