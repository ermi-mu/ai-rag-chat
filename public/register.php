<?php
require_once 'init.php';

use App\Controllers\AuthController;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';

    $auth = new AuthController();
    $result = $auth->register($username, $email, $password, $role);

    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}

require_once __DIR__ . '/../views/header.php';
?>

<div class="row justify-content-center pt-5">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg">
            <div class="card-header text-center py-3">
                <h3 class="fw-bold mb-0">Create Account</h3>
                <p class="text-muted small mb-0">Join the AI RAG community</p>
            </div>
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm py-2 small"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success border-0 shadow-sm py-2 small"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Username</label>
                        <input type="text" name="username" class="form-control" placeholder="johndoe" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label text-muted fw-bold small text-uppercase">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Role</label>
                        <select name="role" class="form-select text-dark bg-white">
                            <option value="user">User (Chat only)</option>
                            <option value="admin">Admin (Chat + Document)</option>
                        </select>
                    </div>
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary">Sign Up</button>
                    </div>
                    <div class="text-center small">
                        <span class="text-muted">Already have an account?</span>
                        <a href="login.php" class="text-primary fw-bold text-decoration-none ms-1">Sign In</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
