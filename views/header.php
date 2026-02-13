<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI RAG Chatbot</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Marked.js for Markdown -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --secondary-color: #f472b6; /* Brightened from #ec4899 */
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --chat-bg: #1e293b;
            --assistant-msg-bg: #334155;
            --user-msg-bg: #6366f1;
            --text-main: #f8fafc;
            --text-muted: #e2e8f0; /* Even brighter grey */
            --font-main: 'Inter', sans-serif;
            --font-heading: 'Montserrat', sans-serif;
            --glass-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);

            /* Bootstrap Overrides */
            --bs-secondary-color: var(--text-muted) !important;
            --bs-secondary-rgb: 226, 232, 240 !important;
        }

        .text-muted { color: var(--text-muted) !important; }

        body { 
            background-color: var(--bg-color); 
            font-family: var(--font-main);
            color: var(--text-main);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            min-height: 100vh;
        }

        .navbar {
            background: var(--glass-bg) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-family: var(--font-heading);
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.5rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            color: var(--text-main);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--glass-border);
            font-family: var(--font-heading);
            font-weight: 600;
            padding: 1.25rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .form-control, .form-select {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: white;
            padding: 0.75rem 1rem;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.08);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            color: white;
        }

        /* Re-adjust Chat Widget Colors */
        .chat-widget {
            position: fixed;
            bottom: 80px;
            right: 30px;
            width: 360px;
            height: 500px;
            background: var(--chat-bg);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
            border: 1px solid var(--glass-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .chat-header {
            background: var(--primary-gradient);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .chat-header span {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .chat-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #111827;
            color: #d1d5db;
            scroll-behavior: smooth;
        }

        .chat-input-area {
            border-top: 1px solid var(--glass-border);
            padding: 15px 20px;
            background: rgba(255,255,255,0.02);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #chatInput {
            flex: 1;
        }

        /* Message Bubbles */
        .message { 
            margin-bottom: 20px; 
            padding: 12px 18px; 
            border-radius: 18px; 
            max-width: 85%; 
            font-size: 0.95rem; 
            line-height: 1.6;
            position: relative;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        .message.user { 
            align-self: flex-end; 
            background: var(--primary-gradient); 
            color: white; 
            margin-left: auto; 
            border-bottom-right-radius: 4px;
        }

        .message.assistant { 
            align-self: flex-start; 
            background: var(--assistant-msg-bg); 
            color: #f3f4f6; 
            margin-right: auto; 
            border-bottom-left-radius: 4px;
            border: 1px solid var(--glass-border);
        }

        /* Markdown elements inside assistant message */
        .message.assistant h1, .message.assistant h2, .message.assistant h3 {
            font-size: 1.1rem;
            margin-top: 15px;
            margin-bottom: 8px;
            color: #fff;
        }

        .message.assistant p {
            margin-bottom: 12px;
        }

        .message.assistant ul, .message.assistant ol {
            padding-left: 20px;
            margin-bottom: 12px;
        }

        .message.assistant li {
            margin-bottom: 6px;
        }

        .message.assistant pre { 
            background: #000; 
            color: #10b981; 
            padding: 15px; 
            border-radius: 10px; 
            overflow-x: auto; 
            margin: 15px 0;
            font-size: 0.85rem;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .message.assistant code { 
            font-family: 'Fira Code', monospace; 
            background: rgba(255,255,255,0.1); 
            padding: 2px 6px; 
            border-radius: 4px; 
            font-size: 0.9em;
            color: #a855f7;
        }

        .message.assistant blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            color: var(--text-muted);
            font-style: italic;
            margin: 15px 0;
        }

        /* Chat Toggle Button */
        .chat-toggle-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 999;
            font-size: 32px;
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="/">AI RAG Chat</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (\App\Core\Auth::check()): ?>
            <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
            <?php if (\App\Core\Auth::isAdmin()): ?>
                <li class="nav-item"><a class="nav-link" href="/admin.php">Admin</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link" href="/logout.php">Logout (<?= htmlspecialchars(\App\Core\Auth::user()['username'] ?? '') ?>)</a></li>
        <?php else: ?>
            <li class="nav-item"><a class="nav-link" href="/login.php">Login</a></li>
            <li class="nav-item"><a class="nav-link" href="/register.php">Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
