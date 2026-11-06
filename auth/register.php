
<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: /compunent-rig/index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        $errors[] = 'Your form session expired. Please try again.';
    }

    if ($name === '' || strlen($name) > 100) {
        $errors[] = 'Enter a name of up to 100 characters.';
    }

    if (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 190
    ) {
        $errors[] = 'Enter a valid email address.';
    }

    if (strlen($phone) > 20) {
        $errors[] = 'Phone number is too long.';
    }

    if (!is_string($password) || strlen($password) < 12) {
        $errors[] = 'Password must contain at least 12 characters.';
    }

    if (!is_string($password) || strlen($password) > 1024) {
        $errors[] = 'Password is too long.';
    }

    if (!is_string($confirmPassword) || $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare(
            'SELECT id FROM users WHERE email = :email LIMIT 1'
        );

        $check->execute(['email' => $email]);

        if ($check->fetch()) {
            $errors[] = 'Unable to register with these details. Please try another email or sign in.';
        }
    }

    if (!$errors) {
        $passwordHash = password_hash(
            $password,
            PASSWORD_DEFAULT
        );

        try {
            $insert = $pdo->prepare("
                INSERT INTO users
                    (name, email, password_hash, phone, role, status)
                VALUES
                    (:name, :email, :password_hash, :phone,
                     'customer', 'active')
            ");

            $insert->execute([
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'phone' => $phone !== '' ? $phone : null,
            ]);

            header('Location: /compunent-rig/auth/login.php?registered=1');
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());

            if ($e->getCode() === '23000') {
                $errors[] = 'Unable to register with these details. Please try another email or sign in.';
            } else {
                http_response_code(500);
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once __DIR__ . '/../includes/header.php';

?>

<section class="section auth-page auth-register-page">
    <div class="container auth-page-container">

        <div class="auth-visual">
            <div class="auth-visual-content">
                <span class="auth-eyebrow">JOIN COMPUNENT RIG</span>

                <h1>Build something powerful.</h1>

                <p>
                    Create your account and discover computer components
                    for your next PC build.
                </p>

                <div class="auth-visual-features">
                    <div>
                        <span>✓</span>
                        Manage your purchases
                    </div>
                    <div>
                        <span>✓</span>
                        Keep track of your orders
                    </div>
                    <div>
                        <span>✓</span>
                        Explore the PC Builder
                    </div>
                </div>
            </div>

            <div class="auth-visual-decoration"></div>
        </div>

        <div class="auth-form-panel">

            <div class="auth-form-heading">
                <span class="auth-eyebrow">GET STARTED</span>
                <h2>Create account</h2>
                <p>Enter your details to create your customer account.</p>
            </div>

            <?php foreach ($errors as $error): ?>
                <div class="auth-alert auth-alert-error">
                    <?= e($error) ?>
                </div>
            <?php endforeach; ?>

            <form method="POST" action="register.php" class="auth-form">

                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e(csrfToken()) ?>"
                >

                <div class="auth-field">
                    <label for="name">Full name</label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        maxlength="100"
                        autocomplete="name"
                        placeholder="Enter your full name"
                        value="<?= e($name) ?>"
                        required
                    >
                </div>

                <div class="auth-field">
                    <label for="email">Email address</label>

                    <input
                        id="email"
                        name="email"
                        type="email"
                        maxlength="190"
                        autocomplete="email"
                        placeholder="you@example.com"
                        value="<?= e($email) ?>"
                        required
                    >
                </div>

                <div class="auth-field">
                    <label for="phone">Phone number <span>(optional)</span></label>

                    <input
                        id="phone"
                        name="phone"
                        type="tel"
                        maxlength="20"
                        autocomplete="tel"
                        placeholder="Enter your phone number"
                        value="<?= e($phone) ?>"
                    >
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>

                    <div class="auth-password-wrap">
                        <input
                            id="password"
                            name="password"
                            type="password"
                            minlength="12"
                            maxlength="1024"
                            autocomplete="new-password"
                            placeholder="At least 12 characters"
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

                <div class="auth-field">
                    <label for="confirm_password">Confirm password</label>

                    <div class="auth-password-wrap">
                        <input
                            id="confirm_password"
                            name="confirm_password"
                            type="password"
                            minlength="12"
                            maxlength="1024"
                            autocomplete="new-password"
                            placeholder="Enter password again"
                            required
                        >

                        <button
                            type="button"
                            class="auth-password-toggle"
                            data-target="confirm_password"
                            aria-label="Show password"
                            aria-pressed="false"
                        >
                            Show
                        </button>
                    </div>
                </div>

                <button class="button auth-submit" type="submit">
                    Create Account
                </button>

            </form>

            <div class="auth-switch">
                Already have an account?
                <a href="login.php">Sign in</a>
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
