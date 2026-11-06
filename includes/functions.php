
<?php

/**
 * Escape values before displaying them in HTML.
 */
function e($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}

/**
 * Format prices in Indian Rupees.
 */
function formatPrice($amount): string
{
    return '₹' . number_format((float) $amount, 2);
}

/**
 * Fetch active products for the homepage.
 */
function getFeaturedProducts(PDO $pdo, int $limit = 8): array
{
    $limit = max(1, min($limit, 24));

    $sql = "
        SELECT
            p.id,
            p.name,
            p.slug,
            p.brand,
            p.price,
            p.stock_quantity,
            c.name AS category_name,
            (
                SELECT pi.image_path
                FROM product_images AS pi
                WHERE pi.product_id = p.id
                  AND pi.is_primary = 1
                ORDER BY pi.id ASC
                LIMIT 1
            ) AS image_path
        FROM products AS p
        INNER JOIN categories AS c
            ON c.id = p.category_id
        WHERE p.status = 'active'
        ORDER BY p.created_at DESC, p.id DESC
        LIMIT $limit
    ";

    $stmt = $pdo->query($sql);

    return $stmt->fetchAll();
}
