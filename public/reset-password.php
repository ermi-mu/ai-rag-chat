<?php
require_once 'init.php';

use App\Controllers\AuthController;

$message = '';
$msgType = '';
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $token = $_POST['token'] ?? '';
    $newPassword = $_POST['password'] ?? '';
    
    $auth = new AuthController();
    $result = $auth->resetPassword($email, $token, $newPassword);
    
    $message = $result['message'];
    $msgType = $result['success'] ? 'success' : 'danger';
    
    if ($result['success']) {
        // Redirect to login after a short delay or show simple link
        $success = true;
    }
}

require_once __DIR__ . '/../views/header.php';
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-header text-center py-3">
                <h3 class="fw-bold mb-0">Set New Password</h3>
                <p class="text-muted small mb-0">Enter your reset token and new password</p>
            </div>
            <div class="card-body p-4">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $msgType ?> border-0 shadow-sm mb-4">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success)): ?>
                    <div class="d-grid">
                        <a href="login.php" class="btn btn-primary">Login with New Password</a>
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">Reset Token</label>
                            <input type="text" name="token" class="form-control" value="<?= htmlspecialchars($token) ?>" placeholder="6-digit code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-muted fw-bold small text-uppercase">New Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary shadow-sm">Update Password</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
