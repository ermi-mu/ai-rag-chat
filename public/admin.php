<?php
require_once 'init.php';

use App\Controllers\AdminController;
use App\Core\Auth;

Auth::requireLogin();

$controller = new AdminController();
$message = '';
$docs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['document'])) {
        $result = $controller->upload($_FILES['document']);
        $message = $result['message'];
        $msgType = $result['success'] ? 'success' : 'danger';
    } elseif (isset($_POST['url'])) {
        $result = $controller->scrape($_POST['url']);
        $message = $result['message'];
        $msgType = $result['success'] ? 'success' : 'danger';
        if ($result['success']) {
            $websiteDetail = $result['description'];
        }
    }
}

$docs = $controller->index();

require_once __DIR__ . '/../views/header.php';
?>

<div class="row gy-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold mb-1" style="font-family: 'Montserrat', sans-serif;">Knowledge <span class="text-primary">Admin Panel</span></h1>
                <p class="text-muted mb-0">Manage and process your documents for AI retrieval.</p>
            </div>
            <div class="badge bg-primary px-3 py-2 rounded-pill">Admin Access</div>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $msgType ?> border-0 shadow-sm mb-4"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($websiteDetail)): ?>
            <div class="card border-0 shadow-sm mb-4 bg-info bg-opacity-10">
                <div class="card-header border-0 pb-0">
                    <h5 class="fw-bold text-info"><span class="me-2">✨</span> Website Details</h5>
                </div>
                <div class="card-body p-4">
                    <div class="text-white bg-dark bg-opacity-50 p-4 rounded-4 shadow-inner" style="line-height: 1.8; font-size: 1.1rem; border: 1px solid rgba(255,255,255,0.05);">
                        <?= nl2br(htmlspecialchars($websiteDetail)) ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Upload Section -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header border-0 pb-0">
                <h5 class="fw-bold"><span class="me-2">📤</span> Upload Document</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <div class="upload-zone mb-4 p-4 text-center border-2 border-dashed rounded-4 position-relative" style="border-color: var(--glass-border); transition: all 0.3s ease;">
                        <input class="form-control position-absolute top-0 start-0 opacity-0 h-100 w-100 cursor-pointer" type="file" id="document" name="document" required style="z-index: 10;">
                        <div class="py-3">
                            <h2 class="display-4 text-primary opacity-50 mb-3">📄</h2>
                            <p class="mb-1 fw-bold">Click to Upload</p>
                            <p class="text-muted small">PDF or TXT files only</p>
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary shadow-sm btn-lg">Process Document</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Scrape Section -->
        <div class="card shadow-sm border-0">
            <div class="card-header border-0 pb-0">
                <h5 class="fw-bold"><span class="me-2">🌐</span> Scrape Website</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label for="url" class="form-label text-muted small fw-bold text-uppercase">Website URL</label>
                        <input type="url" name="url" id="url" class="form-control" placeholder="https://example.com" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-info text-white shadow-sm btn-lg">Scrape & Index</button>
                    </div>
                    <p class="text-muted small text-center mt-3 mb-0">We'll crawl the site and generate an AI summary.</p>
                </form>
            </div>
        </div>
    </div>

    <!-- Documents List Section -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 overflow-hidden bg-white text-dark">
            <div class="card-header border-0 d-flex justify-content-between align-items-center bg-light border-bottom">
                <h5 class="fw-bold mb-0 text-dark"><span class="me-2">📚</span> Indexed Documents</h5>
                <span class="badge bg-primary rounded-pill"><?= count($docs) ?> Files</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3 text-dark small text-uppercase fw-bold">Filename</th>
                                <th class="py-3 text-dark small text-uppercase fw-bold text-end px-4">Uploaded Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($docs as $doc): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="<?= ($doc['is_url'] ?? false) ? 'bg-info' : 'bg-primary' ?> bg-opacity-10 p-2 rounded-3 me-3">
                                            <span class="fs-5"><?= ($doc['is_url'] ?? false) ? '🌐' : '📄' ?></span>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-black d-block"><?= htmlspecialchars($doc['filename']) ?></span>
                                            <?php if (!empty($doc['description'])): ?>
                                                <button class="btn btn-sm btn-link text-info p-0 small text-decoration-none" type="button" data-bs-toggle="collapse" data-bs-target="#desc-<?= $doc['id'] ?>">
                                                    <span class="me-1">✨</span> View AI Profile
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (!empty($doc['description'])): ?>
                                        <div class="collapse mt-2" id="desc-<?= $doc['id'] ?>">
                                            <div class="card card-body bg-light border-0 shadow-inner small text-dark p-3 rounded-3" style="font-size: 0.9rem; line-height: 1.6;">
                                                <div class="fw-bold text-info mb-1 small text-uppercase">AI Website Summary</div>
                                                <?= nl2br(htmlspecialchars($doc['description'])) ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 text-end px-4 align-top">
                                    <span class="text-info small fw-bold"><?= date('M j, Y • H:i', strtotime($doc['uploaded_at'])) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($docs)): ?>
                            <tr>
                                <td colspan="2" class="text-center py-5">
                                    <div class="text-muted opacity-50 mb-3 fs-1">🕳️</div>
                                    <p class="text-muted mb-0">No documents indexed yet.</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.upload-zone:hover {
    border-color: var(--primary-color) !important;
    background: rgba(99, 102, 241, 0.05);
}
.cursor-pointer { cursor: pointer; }
.bg-light.opacity-10 { background-color: rgba(255,255,255,0.02) !important; }
</style>
<script>
document.getElementById('document').addEventListener('change', function(e) {
    const fileName = e.target.files[0] ? e.target.files[0].name : 'Click to Upload';
    const display = this.parentElement.querySelector('p.mb-1');
    display.innerText = fileName;
    display.classList.add('text-primary');
});
</script>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
