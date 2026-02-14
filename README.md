# 🤖 AI RAG Chatbot (Google Gemini Edition)

![AI RAG Chatbot Hero](https://images.unsplash.com/photo-1677442136019-21780ecad995?auto=format&fit=crop&q=80&w=1200&h=400)

[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777bb4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-4479a1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![Google Gemini](https://img.shields.io/badge/AI-Google%20Gemini-4285f4?style=for-the-badge&logo=google&logoColor=white)](https://deepmind.google/technologies/gemini/)
[![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)](LICENSE)

A high-performance, production-ready **Retrieval-Augmented Generation (RAG)** chatbot built with Core PHP and the **Google Gemini API**. This application transforms your local documents and website links into a conversational knowledge base.

---

## 🚀 Key Features

### 🧠 Intelligent RAG Engine
- **Multi-Source Indexing**: Upload PDF/TXT files or scrape entire Websites.
- **Advanced Embeddings**: Powered by `gemini-embedding-001` for highly accurate semantic search.
- **Contextual Awareness**: Chatbot answers questions by "reading" your specific data.

### 🌐 Website Scraping & Analysis
- **One-Click Scraping**: Provide a URL, and the system extracts, cleans, and indexes the content.
- **AI-Generated Profiles**: Every indexed website gets a detailed AI-generated description summarizing its purpose and features.

### 🔐 Enterprise-Grade Security
- **RBAC (Role-Based Access Control)**: Distinctive 'User' and 'Admin' roles.
- **Secure Authentication**: Password hashing and protected admin routes.
- **Session Management**: Secure user sessions across the app.

### 💄 Premium User Experience
- **Glassmorphism UI**: Modern, sleek dark theme with vibrant gradients.
- **One-Click Login**: Seamless Google OAuth 2.0 integration for users.
- **Real-Time Streaming**: SSE (Server-Sent Events) for a smooth, typing-effect chat response.
- **Chat Persistence**: Full history storage allowing users to resume conversations.

---

## 🛠️ Tech Stack
- **Backend**: Core PHP 8.1+
- **Database**: MySQL 8.0+ (with JSON support for embeddings)
- **AI Engine**: Google Gemini API (`gemini-1.5-flash-preview`)
- **Frontend**: Bootstrap 5, Vanilla JS, marked.js (Markdown rendering)
- **Scraper**: Custom `WebScraperService` with DOM parsing and fallback logic.
- **Dependencies**: `google/apiclient`, `smalot/pdfparser`, `vlucas/phpdotenv`.

---

## 📦 Project Structure
```text
├── public/                 # Web root
│   ├── assets/             # CSS, JS, Images
│   ├── uploads/            # Indexed document storage
│   ├── index.php           # Hero Landing Page
│   └── admin.php           # Knowledge Base Dashboard
├── src/                    # Application source
│   ├── Controllers/        # Request handlers (Admin, Chat, Auth)
│   ├── Services/           # Business logic (Gemini, Scraper, Doc Processing)
│   └── Core/               # System utilities (Database, Auth)
├── views/                  # UI components (Header, Footer)
└── schema.sql              # Database structure
```

---

## ⚙️ Setup Instructions

### 1. Prerequisites
- **XAMPP** (or any LAMP/WAMP stack)
- **Composer** installed globally

### 2. Database Initialization
Create a database named `rag_chat_app` and import `schema.sql`:
```bash
mysql -u root -p rag_chat_app < schema.sql
```

### 3. Environment Configuration
Create a `.env` file in the root directory:
```ini
DB_HOST=localhost
DB_NAME=rag_chat_app
DB_USER=root
DB_PASS=
GEMINI_API_KEY=your_google_gemini_api_key
GEMINI_MODEL=gemini-1.5-flash-preview
EMBEDDING_MODEL=gemini-embedding-001

# Google OAuth
GOOGLE_CLIENT_ID=your_id_here
GOOGLE_CLIENT_SECRET=your_secret_here
GOOGLE_REDIRECT_URI=http://localhost:8000/google-callback.php
```

### 4. Google Cloud Setup
For Google Login to work:
1. Create a project in [Google Cloud Console](https://console.cloud.google.com/).
2. Setup **OAuth Consent Screen** (External).
3. Create **OAuth 2.0 Client ID** (Web application).
4. Add `http://localhost:8000/google-callback.php` to **Authorized redirect URIs**.

### 4. Install Dependencies
```bash
composer install
```

### 5. Launch
Start the built-in PHP server:
```bash
php -S localhost:8000 -t public
```

---

## 📄 Documentation & Usage
1. **Register**: Create an account via `register.php`.
2. **Admin Access**: Log in as an admin to access the dashboard.
3. **Index Knowledge**: Upload documents or scrape URLs in the Knowledge Base Admin.
4. **Chat**: Use the persistent chat widget on any page to interact with your data.

---

## ⚖️ License
Distributed under the MIT License. See `LICENSE` for more information.
