<?php
require_once 'init.php';

use App\Controllers\AuthController;

$message = '';
$msgType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $auth = new AuthController();
    $result = $auth->requestReset($email);
    
    $message = $result['message'];
    $msgType = $result['success'] ? 'success' : 'danger';
    if ($result['success']) {
        $sentSuccess = true;
    }
}

require_once __DIR__ . '/../views/header.php';
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-header text-center py-3">
                <h3 class="fw-bold mb-0">Reset Password</h3>
                <p class="text-muted small mb-0">Enter your email to request a reset token</p>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $msgType ?> border-0 shadow-sm mb-4">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($sentSuccess)): ?>
                    <div class="d-grid">
                        <a href="reset-password.php?email=<?= urlencode($_POST['email']) ?>" class="btn btn-primary">Go to Reset Password Page</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary shadow-sm">Request Reset</button>
                        </div>
                        <div class="text-center small">
                            <a href="login.php" class="text-muted text-decoration-none">Return to Login</a>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
