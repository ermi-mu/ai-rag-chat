<?php
require_once 'init.php';
require_once __DIR__ . '/../views/header.php';
?>

<div class="container">
    <div class="row align-items-center min-vh-100" style="margin-top: -80px;">
        <div class="col-lg-6 text-start">
            <h6 class="text-primary fw-bold text-uppercase mb-3" style="letter-spacing: 2px;">Advanced RAG Technology</h6>
            <h1 class="display-4 fw-bold mb-3" style="font-family: 'Montserrat', sans-serif;">Smart Neural <span class="text-primary">Index</span> Explorer</h1>
            <p class="lead text-muted mb-4 fs-5">Transform your PDF, TXT, and Websites into a smart knowledge base. Experience the future of information retrieval with our premium AI-powered RAG assistant.</p>
            
            <div class="d-flex gap-2">
                <?php if (\App\Core\Auth::check()): ?>
                    <button id="heroChatBtn" class="btn btn-primary px-4">Go Chat</button>
                    <?php if (\App\Core\Auth::isAdmin()): ?>
                        <a href="admin.php" class="btn btn-outline-info px-4 fw-bold">Admin Dashboard</a>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="register.php" class="btn btn-primary px-4">Join Now</a>
                    <a href="login.php" class="btn btn-outline-light px-4">Login</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-6 d-none d-lg-block text-center mt-3 mt-lg-0">
            <div class="p-4 bg-primary bg-opacity-10 rounded-circle d-inline-block position-relative">
                <div class="p-4 bg-primary bg-opacity-20 rounded-circle">
                     <span style="font-size: 10rem;">🤖</span>
                </div>
                <div class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger p-3 shadow-lg">NEW</div>
            </div>
        </div>
    </div>
</div>

<!-- Chat Widget -->
<?php if (\App\Core\Auth::check()): ?>
<div class="chat-toggle-btn" id="chatToggle">💬</div>

<div class="chat-widget" id="chatWidget" style="display: none;">
    <div class="chat-header" id="chatHeader">
        <span>AI Assistant</span>
        <button type="button" class="btn-close btn-close-white float-end" id="chatClose"></button>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="message assistant">Hello! Ask me anything about the uploaded documents.</div>
    </div>
    <div class="chat-input-area">
        <input type="text" id="chatInput" class="form-control" placeholder="Type..." autocomplete="off">
        <button id="chatSend" class="btn btn-primary ms-2">Send</button>
    </div>
</div>

<script src="/assets/js/chat.js"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/../views/footer.php'; ?>
