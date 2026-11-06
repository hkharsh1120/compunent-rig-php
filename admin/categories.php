
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin($pdo);

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

$category = [
    'id' => null,
    'name' => '',
    'slug' => '',
    'description' => '',
];

$errors = [];

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $found = $stmt->fetch();

    if (!$found) {
        http_response_code(404);
        exit('Category not found.');
    }

    $category = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        exit('Invalid form token.');
    }

    $category['name'] = trim($_POST['name'] ?? '');
    $category['description'] = trim($_POST['description'] ?? '');

    $slug = strtolower($category['name']);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    $category['slug'] = trim($slug, '-');

    if ($category['name'] === '' || strlen($category['name']) > 100) {
        $errors[] = 'Enter a category name under 100 characters.';
    }

    if (strlen($category['description']) > 65535) {
        $errors[] = 'Description is too long.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            SELECT id FROM categories
            WHERE slug = ? AND id != ?
            LIMIT 1
        ");

        $stmt->execute([
            $category['slug'],
            $editId ?: 0
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'This category name already exists.';
        }
    }

    if (!$errors) {
        if ($editId) {
            $stmt = $pdo->prepare("
                UPDATE categories
                SET name = ?, slug = ?, description = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $category['name'],
                $category['slug'],
                $category['description'] ?: null,
                $editId
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO categories (name, slug, description)
                VALUES (?, ?, ?)
            ");

            $stmt->execute([
                $category['name'],
                $category['slug'],
                $category['description'] ?: null
            ]);
        }

        header('Location: categories.php?saved=1');
        exit;
    }
}

$categories = $pdo->query("
    SELECT
        c.id,
        c.name,
        c.slug,
        c.description,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.slug, c.description
    ORDER BY c.name ASC
")->fetchAll();

$pageTitle = 'Category Management';

require_once __DIR__ . '/includes/header.php';

?>

<main class="admin-main">

    <h1>Category Management</h1>

    <?php if (isset($_GET['saved'])): ?>
        <p class="admin-success">Category saved successfully.</p>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <p class="admin-success">Category deleted successfully.</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p class="admin-error"><?= e($error) ?></p>
    <?php endforeach; ?>

    <section class="admin-section">

        <h2><?= $editId ? 'Edit Category' : 'Add Category' ?></h2>

        <form method="POST" class="admin-product-form">

            <input type="hidden" name="csrf_token"
                   value="<?= e(csrfToken()) ?>">

            <label>Category Name</label>

            <input type="text" name="name" maxlength="100"
                   value="<?= e($category['name']) ?>" required>

            <label>Description</label>

            <textarea name="description" rows="3"><?= e($category['description'] ?? '') ?></textarea>

            <button type="submit" class="admin-primary-btn">
                <?= $editId ? 'Update Category' : 'Save Category' ?>
            </button>

            <?php if ($editId): ?>
                <a href="categories.php">Cancel Editing</a>
            <?php endif; ?>

        </form>

    </section>

    <section class="admin-section" style="margin-top:25px;">

        <h2>All Categories (<?= count($categories) ?>)</h2>

        <div class="admin-table-wrapper">

            <table class="admin-table">

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($categories as $item): ?>

                        <tr>
                            <td><?= e($item['name']) ?></td>
                            <td><?= e($item['slug']) ?></td>
                            <td><?= (int) $item['product_count'] ?></td>

                            <td>
                                <a href="categories.php?edit=<?= (int) $item['id'] ?>">
                                    Edit
                                </a>

                                <form method="POST"
                                      action="category-delete.php"
                                      class="admin-inline-form"
                                      onsubmit="return confirm('Delete this category?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= e(csrfToken()) ?>">

                                    <input type="hidden" name="category_id"
                                           value="<?= (int) $item['id'] ?>">

                                    <button type="submit" class="admin-delete-btn">
                                        Delete
                                    </button>

                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    <?php if (!$categories): ?>
                        <tr><td colspan="4">No categories found.</td></tr>
                    <?php endif; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
