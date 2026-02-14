<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Auth;
use App\Services\GeminiService;
use App\Services\DocumentService;
use PDO;

class AdminController
{
    private PDO $db;
    private GeminiService $ai;
    private DocumentService $docService;
    private \App\Services\WebScraperService $scraper;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->db = Database::getConnection();
        $this->ai = new GeminiService();
        $this->docService = new DocumentService();
        $this->scraper = new \App\Services\WebScraperService();
        $this->runMigrations();
    }

    private function runMigrations(): void
    {
        // Add description column
        try {
            $this->db->exec("ALTER TABLE documents ADD COLUMN description TEXT NULL");
        } catch (\PDOException $e) {
            // Already exists or other error
        }
        
        // Add is_url column
        try {
            $this->db->exec("ALTER TABLE documents ADD COLUMN is_url BOOLEAN DEFAULT FALSE");
        } catch (\PDOException $e) {
            // Already exists or other error
        }

        // Add google_id column to users table
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) UNIQUE NULL");
        } catch (\PDOException $e) {
            // Already exists or other error
        }

        // Add reset token columns to users table
        try {
            $this->db->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
        } catch (\PDOException $e) {
            // Already exists or other error
        }
    }

    public function index(): array
    {
        $stmt = $this->db->query("SELECT * FROM documents ORDER BY uploaded_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generateDescription(string $text): string
    {
        $prompt = "Please provide a detailed, professional, and concise summary of the following website content. Focus on its purpose, main features, and target audience: \n\n" . substr($text, 0, 10000);
        
        $messages = [
            ['role' => 'system', 'content' => 'You are a professional web analyst. Provide a detailed summary.'],
            ['role' => 'user', 'content' => $prompt]
        ];

        return $this->ai->generateResponse($messages);
    }

    public function scrape(string $url): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['success' => false, 'message' => 'Invalid URL provided.'];
        }

        try {
            // 1. Scrape Text & Title
            $scraped = $this->scraper->scrape($url);
            $text = $scraped['content'];
            $title = $scraped['title'] ?: $url;

            if (empty(trim($text))) {
                  return ['success' => false, 'message' => 'Could not extract content from the website. It may be blocked or protected.'];
            }

            // 2. Generate Description (using up to 10k chars for summary)
            $description = $this->generateDescription($text);

            // 3. Save "Document" Record with Title and Description
            $stmt = $this->db->prepare("INSERT INTO documents (filename, filepath, description, is_url) VALUES (?, ?, ?, TRUE)");
            $stmt->execute([$title, $url, $description]);
            $docId = $this->db->lastInsertId();

            // 4. Chunk & Index
            $chunks = $this->docService->chunkText($text, 800);
            $stmtChunk = $this->db->prepare("INSERT INTO document_chunks (document_id, content, embedding, token_count) VALUES (?, ?, ?, ?)");
            
            foreach ($chunks as $chunk) {
                if (empty(trim($chunk))) continue;
                $embedding = $this->ai->getEmbedding($chunk);
                $stmtChunk->execute([$docId, $chunk, json_encode($embedding), strlen($chunk) / 4]);
            }

            return [
                'success' => true, 
                'message' => 'Website "' . $title . '" successfully scraped and indexed.',
                'description' => $description
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Scraping Error: ' . $e->getMessage()];
        }
    }

    public function upload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error.'];
        }

        $allowedTypes = ['application/pdf', 'text/plain'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['success' => false, 'message' => 'Only PDF and TXT files are allowed.'];
        }

        $filename = basename($file['name']);
        $targetDir = __DIR__ . '/../../public/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $targetPath = $targetDir . uniqid() . '_' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return ['success' => false, 'message' => 'Failed to move uploaded file.'];
        }

        try {
            // 1. Extract Text
            $text = $this->docService->extractText($targetPath, $file['type']);
            if (empty(trim($text))) {
                  return ['success' => false, 'message' => 'Could not extract text from file.'];
            }

            // 2. Save Document Record
            $stmt = $this->db->prepare("INSERT INTO documents (filename, filepath) VALUES (?, ?)");
            $stmt->execute([$filename, $targetPath]);
            $docId = $this->db->lastInsertId();

            // 3. Chunk Text
            $chunks = $this->docService->chunkText($text, 800);

            // 4. Generate Embeddings & Store
            $stmtChunk = $this->db->prepare("INSERT INTO document_chunks (document_id, content, embedding, token_count) VALUES (?, ?, ?, ?)");
            
            foreach ($chunks as $chunk) {
                if (empty(trim($chunk))) continue;
                $embedding = $this->ai->getEmbedding($chunk);
                $stmtChunk->execute([
                    $docId, 
                    $chunk, 
                    json_encode($embedding), 
                    strlen($chunk) / 4 // Crude token count estimate
                ]);
            }

            return ['success' => true, 'message' => 'Document processed and indexed successfully.'];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
