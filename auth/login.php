
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /compunent-rig/index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $error = 'Your form session expired. Please try again.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
        || !is_string($password)
        || $password === ''
        || strlen($password) > 1024
    ) {
        $error = 'Invalid email or password.';
    } else {
        $stmt = $pdo->prepare("
            SELECT id, name, email, password_hash, role, status
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if (
            $user
            && $user['status'] === 'active'
            && password_verify($password, $user['password_hash'])
        ) {
            session_regenerate_id(true);

            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];

            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: /compunent-rig/index.php');
            exit;
        }

        $error = 'Invalid email or password.';
    }
}

$pageTitle = 'Sign In';
require_once __DIR__ . '/../includes/header.php';

?>

<section class="section auth-page">
    <div class="container auth-page-container">

        <div class="auth-visual">
            <div class="auth-visual-content">
                <span class="auth-eyebrow">WELCOME BACK</span>

                <h1>Your next build starts here.</h1>

                <p>
                    Sign in to manage your orders, explore components
                    and continue building your perfect PC.
                </p>

                <div class="auth-visual-features">
                    <div>
                        <span>✓</span>
                        Explore quality computer components
                    </div>
                    <div>
                        <span>✓</span>
                        Track your orders
                    </div>
                    <div>
                        <span>✓</span>
                        Build your custom PC
                    </div>
                </div>
            </div>

            <div class="auth-visual-decoration"></div>
        </div>

        <div class="auth-form-panel">

            <div class="auth-form-heading">
                <span class="auth-eyebrow">YOUR ACCOUNT</span>
                <h2>Sign in</h2>
                <p>Enter your details to access your account.</p>
            </div>

            <?php if (isset($_GET['registered'])): ?>
                <div class="auth-alert auth-alert-success">
                    Registration successful. You can now sign in.
                </div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert-error">
                    <?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="auth-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <div class="auth-field">
                    <label for="email">Email address</label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="190"
                        autocomplete="username"
                        placeholder="you@example.com"
                        value="<?= e($email) ?>"
                        required
                    >
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>

                    <div class="auth-password-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            maxlength="1024"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="auth-password-toggle"
                            data-target="password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <button class="button auth-submit" type="submit">
                    Sign In
                </button>

            </form>

            <div class="auth-switch">
                Don't have an account?
                <a href="register.php">Create an account</a>
            </div>

            <a href="/compunent-rig/" class="auth-back-link">
                ← Back to Compunent Rig
            </a>

        </div>

    </div>
</section>

<script>
document.querySelectorAll('.auth-password-toggle').forEach(function (button) {
    button.addEventListener('click', function () {
        const input = document.getElementById(button.dataset.target);

        if (!input) {
            return;
        }

        const showPassword = input.type === 'password';

        input.type = showPassword ? 'text' : 'password';
        button.textContent = showPassword ? 'Hide' : 'Show';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
