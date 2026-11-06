
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$isEdit = $productId !== null && $productId !== false && $productId > 0;

$product = [
    'category_id' => '',
    'name' => '',
    'slug' => '',
    'sku' => '',
    'brand' => '',
    'description' => '',
    'price' => '',
    'stock_quantity' => 0,
    'product_type' => 'component',
    'status' => 'active',
];

$errors = [];

$compatibility = [
    'component_type' => '',
    'socket' => '',
    'ram_type' => '',
    'capacity_gb' => '',
    'wattage' => '',
    'recommended_psu_wattage' => '',
    'storage_interface' => '',
    'supported_storage_interfaces' => '',
    'max_ram_capacity_gb' => '',
];


if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $existingProduct = $stmt->fetch();

    if (!$existingProduct) {
        http_response_code(404);
        exit('Product not found.');
    }

    $product = $existingProduct;
    
$stmt = $pdo->prepare("
SELECT *
FROM product_compatibility
WHERE product_id = ?
LIMIT 1
");

$stmt->execute([$productId]);

$savedCompatibility = $stmt->fetch();

if ($savedCompatibility) {
$compatibility = array_merge(
    $compatibility,
    $savedCompatibility
);
}

}

$categories = $pdo->query("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid form token. Refresh and try again.');
    }

    $product['category_id'] = filter_var(
        $_POST['category_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $product['name'] = trim($_POST['name'] ?? '');
    $product['sku'] = trim($_POST['sku'] ?? '');
    $product['brand'] = trim($_POST['brand'] ?? '');
    $product['description'] = trim($_POST['description'] ?? '');
    $product['price'] = trim($_POST['price'] ?? '');
    $product['stock_quantity'] = filter_var(
        $_POST['stock_quantity'] ?? null,
        FILTER_VALIDATE_INT
    );

    $product['product_type'] = $_POST['product_type'] ?? '';
    $product['status'] = $_POST['status'] ?? '';

    $slug = strtolower($product['name']);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $product['slug'] = trim($slug, '-');
    $compatibility['component_type'] = trim($_POST['component_type'] ?? '');
$compatibility['socket'] = trim($_POST['socket'] ?? '');
$compatibility['ram_type'] = trim($_POST['ram_type'] ?? '');
$compatibility['capacity_gb'] = trim($_POST['capacity_gb'] ?? '');
$compatibility['wattage'] = trim($_POST['wattage'] ?? '');
$compatibility['recommended_psu_wattage'] = trim($_POST['recommended_psu_wattage'] ?? '');
$compatibility['storage_interface'] = trim($_POST['storage_interface'] ?? '');
$compatibility['supported_storage_interfaces'] = trim($_POST['supported_storage_interfaces'] ?? '');
$compatibility['max_ram_capacity_gb'] = trim($_POST['max_ram_capacity_gb'] ?? '');

    if (!$product['category_id'] || $product['category_id'] < 1) {
        $errors[] = 'Select a valid category.';
    }

    if ($product['name'] === '' || strlen($product['name']) > 200) {
        $errors[] = 'Product name is required and must be under 200 characters.';
    }

    if ($product['sku'] === '' || strlen($product['sku']) > 64) {
        $errors[] = 'Enter a valid SKU.';
    }

    if (strlen($product['brand']) > 100) {
        $errors[] = 'Brand must be under 100 characters.';
    }

    if (!is_numeric($product['price']) || (float) $product['price'] < 0
        || (float) $product['price'] > 99999999.99) {
        $errors[] = 'Enter a valid product price.';
    }

    if (
        $product['stock_quantity'] === false
        || $product['stock_quantity'] < 0
    ) {
        $errors[] = 'Enter a valid stock quantity.';
    }

    if (strlen($product['description']) > 65535) {
        $errors[] = 'Product description is too long.';
    }

    if (!in_array($product['product_type'], ['component', 'peripheral', 'accessory', 'other'], true)) {
        $errors[] = 'Invalid product type.';
    }

    if (!in_array($product['status'], ['active', 'inactive'], true)) {
        $errors[] = 'Invalid product status.';
    }

    // Validate category.
    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$product['category_id']]);

        if (!$stmt->fetch()) {
            $errors[] = 'Selected category does not exist.';
        }
    }
    
$allowedComponentTypes = [
    '',
    'cpu',
    'motherboard',
    'ram',
    'gpu',
    'storage',
    'psu',
    'case'
];

if (!in_array(
    $compatibility['component_type'],
    $allowedComponentTypes,
    true
)) {
    $errors[] = 'Invalid PC component type.';
}

$numericCompatibilityFields = [
    'capacity_gb',
    'wattage',
    'recommended_psu_wattage',
    'max_ram_capacity_gb'
];

foreach ($numericCompatibilityFields as $field) {

    $value = $compatibility[$field];

    if ($value !== '') {

        $number = filter_var($value, FILTER_VALIDATE_INT);

        if ($number === false || $number < 1 || $number > 100000) {
            $errors[] = 'Invalid compatibility value: ' . $field;
        }
    }
}

if (strlen($compatibility['socket']) > 50) {
    $errors[] = 'CPU socket information is too long.';
}

if (strlen($compatibility['ram_type']) > 30) {
    $errors[] = 'RAM type information is too long.';
}

if (strlen($compatibility['storage_interface']) > 50) {
    $errors[] = 'Storage interface information is too long.';
}

if (strlen($compatibility['supported_storage_interfaces']) > 255) {
    $errors[] = 'Supported storage interfaces are too long.';
}


    // Validate image upload.
    $uploadedImage = null;

    if (
        isset($_FILES['product_image'])
        && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed.';
        } elseif ($_FILES['product_image']['size'] > 3 * 1024 * 1024) {
            $errors[] = 'Image must be smaller than 3 MB.';
        } else {

            $tmpPath = $_FILES['product_image']['tmp_name'];

            $mimeTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);

            if (
                !isset($mimeTypes[$mime])
                || getimagesize($tmpPath) === false
            ) {
                $errors[] = 'Only JPG, PNG and WEBP images are allowed.';
            } else {
                $uploadedImage = [
                    'tmp' => $tmpPath,
                    'extension' => $mimeTypes[$mime],
                ];
            }
        }
    }

    // Check duplicate SKU and slug.
    if (!$errors) {

        $stmt = $pdo->prepare("
            SELECT id
            FROM products
            WHERE (sku = ? OR slug = ?)
              AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $product['sku'],
            $product['slug'],
            $isEdit ? $productId : 0
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'This SKU or product name already exists.';
        }
    }

    if (!$errors) {

        $uploadDirectory = __DIR__ . '/../uploads/products';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0755, true);
        }

        $newImagePath = null;

        try {

            if ($uploadedImage !== null) {

                $filename = bin2hex(random_bytes(16))
                    . '.' . $uploadedImage['extension'];

                $destination = $uploadDirectory . '/' . $filename;

                if (!move_uploaded_file($uploadedImage['tmp'], $destination)) {
                    throw new RuntimeException('Could not save product image.');
                }

                $newImagePath = 'uploads/products/' . $filename;
            }

            $pdo->beginTransaction();

            if ($isEdit) {

                $stmt = $pdo->prepare("
                    UPDATE products
                    SET
                        category_id = ?,
                        name = ?,
                        slug = ?,
                        sku = ?,
                        brand = ?,
                        description = ?,
                        price = ?,
                        stock_quantity = ?,
                        product_type = ?,
                        status = ?
                    WHERE id = ?
                ");

                $stmt->execute([
                    $product['category_id'],
                    $product['name'],
                    $product['slug'],
                    $product['sku'],
                    $product['brand'] ?: null,
                    $product['description'] ?: null,
                    $product['price'],
                    $product['stock_quantity'],
                    $product['product_type'],
                    $product['status'],
                    $productId
                ]);

                $savedProductId = $productId;

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO products (
                        category_id, name, slug, sku, brand,
                        description, price, stock_quantity,
                        product_type, status
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $product['category_id'],
                    $product['name'],
                    $product['slug'],
                    $product['sku'],
                    $product['brand'] ?: null,
                    $product['description'] ?: null,
                    $product['price'],
                    $product['stock_quantity'],
                    $product['product_type'],
                    $product['status']
                ]);

                $savedProductId = (int) $pdo->lastInsertId();
            }
            
// Save or remove PC Builder compatibility information.

if ($compatibility['component_type'] === '') {

    $stmt = $pdo->prepare("
        DELETE FROM product_compatibility
        WHERE product_id = ?
    ");

    $stmt->execute([$savedProductId]);

} else {

    $stmt = $pdo->prepare("
        INSERT INTO product_compatibility (
            product_id,
            component_type,
            socket,
            ram_type,
            capacity_gb,
            wattage,
            recommended_psu_wattage,
            storage_interface,
            supported_storage_interfaces,
            max_ram_capacity_gb
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)

        ON DUPLICATE KEY UPDATE
            component_type = VALUES(component_type),
            socket = VALUES(socket),
            ram_type = VALUES(ram_type),
            capacity_gb = VALUES(capacity_gb),
            wattage = VALUES(wattage),
            recommended_psu_wattage = VALUES(recommended_psu_wattage),
            storage_interface = VALUES(storage_interface),
            supported_storage_interfaces = VALUES(supported_storage_interfaces),
            max_ram_capacity_gb = VALUES(max_ram_capacity_gb)
    ");

    $stmt->execute([
        $savedProductId,
        $compatibility['component_type'],
        $compatibility['socket'] ?: null,
        $compatibility['ram_type'] ?: null,
        $compatibility['capacity_gb'] !== ''
            ? (int) $compatibility['capacity_gb'] : null,
        $compatibility['wattage'] !== ''
            ? (int) $compatibility['wattage'] : null,
        $compatibility['recommended_psu_wattage'] !== ''
            ? (int) $compatibility['recommended_psu_wattage'] : null,
        $compatibility['storage_interface'] ?: null,
        $compatibility['supported_storage_interfaces'] ?: null,
        $compatibility['max_ram_capacity_gb'] !== ''
            ? (int) $compatibility['max_ram_capacity_gb'] : null
    ]);
}


            if ($newImagePath !== null) {

                $pdo->prepare("
                    UPDATE product_images
                    SET is_primary = 0
                    WHERE product_id = ?
                ")->execute([$savedProductId]);

                $stmt = $pdo->prepare("
                    INSERT INTO product_images (
                        product_id,
                        image_path,
                        alt_text,
                        is_primary
                    )
                    VALUES (?, ?, ?, 1)
                ");

                $stmt->execute([
                    $savedProductId,
                    $newImagePath,
                    $product['name']
                ]);
            }

            $pdo->commit();

            header('Location: products.php?success=1');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($newImagePath !== null) {
                $file = __DIR__ . '/../' . $newImagePath;

                if (is_file($file)) {
                    unlink($file);
                }
            }

            error_log('Product save error: ' . $e->getMessage());

            $errors[] = 'Unable to save product. Check your data and try again.';
        }
    }
}

$pageTitle = $isEdit ? 'Edit Product' : 'Add Product';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1><?= $isEdit ? 'Edit Product' : 'Add Product' ?></h1>

    <?php foreach ($errors as $error): ?>
        <p class="admin-error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <section class="admin-section">

        <form method="POST" enctype="multipart/form-data" class="admin-product-form">

            <input type="hidden" name="csrf_token" value="<?= e(csrfToken()) ?>">

            <label>Product Name</label>
            <input type="text" name="name" maxlength="200" required
                   value="<?= e($product['name']) ?>">

            <label>SKU</label>
            <input type="text" name="sku" maxlength="64" required
                   value="<?= e($product['sku']) ?>">

            <label>Category</label>
            <select name="category_id" required>
                <option value="">Select Category</option>

                <?php foreach ($categories as $category): ?>
                    <option value="<?= (int) $category['id'] ?>"
                        <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                        <?= e($category['name']) ?>
                    </option>
                <?php endforeach; ?>

            </select>

            <label>Brand</label>
            <input type="text" name="brand" maxlength="100"
                   value="<?= e($product['brand'] ?? '') ?>">

            <label>Description</label>
            <textarea name="description" rows="5"><?= e($product['description'] ?? '') ?></textarea>

            <label>Price (₹)</label>
            <input type="number" name="price" min="0" max="99999999.99"
                   step="0.01" required value="<?= e($product['price']) ?>">

            <label>Stock Quantity</label>
            <input type="number" name="stock_quantity" min="0"
                   required value="<?= e($product['stock_quantity']) ?>">

            <label>Product Type</label>
            <select name="product_type">
                <?php foreach (['component', 'peripheral', 'accessory', 'other'] as $type): ?>
                    <option value="<?= e($type) ?>"
                        <?= $product['product_type'] === $type ? 'selected' : '' ?>>
                        <?= e(ucfirst($type)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
<hr>

<h2>PC Builder Compatibility Specifications</h2>

<p>
    Select a component type and enter its hardware specifications.
    Leave the component type empty for ordinary products.
</p>

<label>PC Component Type</label>

<select name="component_type">

    <option value="">Not a PC Builder component</option>

    <?php foreach (
        ['cpu', 'motherboard', 'ram', 'gpu', 'storage', 'psu', 'case']
        as $type
    ): ?>

        <option
            value="<?= e($type) ?>"
            <?= $compatibility['component_type'] === $type ? 'selected' : '' ?>
        >
            <?= e(ucfirst($type)) ?>
        </option>

    <?php endforeach; ?>

</select>

<label>CPU / Motherboard Socket</label>

<input
    type="text"
    name="socket"
    placeholder="Example: AM5, LGA1700"
    value="<?= e($compatibility['socket']) ?>"
>

<label>RAM Type</label>

<select name="ram_type">

    <option value="">Select RAM Type</option>

    <?php foreach (['DDR3', 'DDR4', 'DDR5'] as $type): ?>

        <option
            value="<?= e($type) ?>"
            <?= $compatibility['ram_type'] === $type ? 'selected' : '' ?>
        >
            <?= e($type) ?>
        </option>

    <?php endforeach; ?>

</select>

<label>RAM Capacity (GB)</label>

<input
    type="number"
    name="capacity_gb"
    min="1"
    max="100000"
    placeholder="Example: 16"
    value="<?= e($compatibility['capacity_gb']) ?>"
>

<label>PSU Wattage (W)</label>

<input
    type="number"
    name="wattage"
    min="1"
    max="100000"
    placeholder="Example: 750"
    value="<?= e($compatibility['wattage']) ?>"
>

<label>GPU Recommended PSU Wattage (W)</label>

<input
    type="number"
    name="recommended_psu_wattage"
    min="1"
    max="100000"
    placeholder="Example: 650"
    value="<?= e($compatibility['recommended_psu_wattage']) ?>"
>

<label>Storage Interface</label>

<input
    type="text"
    name="storage_interface"
    placeholder="Example: NVMe, SATA"
    value="<?= e($compatibility['storage_interface']) ?>"
>

<label>Motherboard Supported Storage Interfaces</label>

<input
    type="text"
    name="supported_storage_interfaces"
    placeholder="Example: NVMe,SATA"
    value="<?= e($compatibility['supported_storage_interfaces']) ?>"
>

<label>Motherboard Maximum RAM Capacity (GB)</label>

<input
    type="number"
    name="max_ram_capacity_gb"
    min="1"
    max="100000"
    placeholder="Example: 128"
    value="<?= e($compatibility['max_ram_capacity_gb']) ?>"
>


            <label>Status</label>
            <select name="status">
                <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>

            <label>Product Image (JPG, PNG, WEBP — max 3 MB)</label>
            <input type="file" name="product_image"
                   accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">

            <button type="submit" class="admin-primary-btn">
                <?= $isEdit ? 'Update Product' : 'Save Product' ?>
            </button>

            <a href="products.php">Cancel</a>

        </form>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
