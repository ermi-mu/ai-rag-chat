<?php
require_once 'init.php';

use App\Controllers\AuthController;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $auth = new AuthController();
    $result = $auth->login($email, $password);

    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'] ?? 'Login failed.';
    }
}

require_once __DIR__ . '/../views/header.php';
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-header text-center py-3">
                <h3 class="fw-bold mb-0">Welcome Back</h3>
                <p class="text-muted small mb-0">Login to your account</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">Sign In</button>
                    </div>
                    <div class="text-center small">
                        <span class="text-muted">Don't have an account?</span>
                        <a href="register.php" class="text-primary fw-bold text-decoration-none ms-1">Create Account</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
