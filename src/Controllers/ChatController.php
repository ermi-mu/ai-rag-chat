<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Auth;
use App\Services\GeminiService;
use PDO;

class ChatController
{
    private PDO $db;
    private GeminiService $ai;

    public function __construct()
    {
        Auth::requireLogin();
        $this->db = Database::getConnection();
        $this->ai = new GeminiService();
    }

    public function handleRequest()
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');

        $input = json_decode(file_get_contents('php://input'), true);
        $message = $input['message'] ?? '';
        $userId = Auth::id();

        if (empty($message)) {
            echo "data: " . json_encode(['error' => 'Message is required']) . "\n\n";
            return;
        }

        // 1. Process User Message
        $this->saveMessage($userId, 'user', $message);

        // 2. Generate Embedding for Question
        try {
            $queryEmbedding = $this->ai->getEmbedding($message);
        } catch (\Exception $e) {
            echo "data: " . json_encode(['error' => 'Embedding Fail: '.$e->getMessage()]) . "\n\n";
            return;
        }

        // 3. Vector Search (RAG)
        $relevantChunks = $this->findRelevantChunks($queryEmbedding, 3);
        
        $context = "";
        foreach ($relevantChunks as $chunk) {
            $context .= $chunk['content'] . "\n---\n";
        }

        // 4. Build Prompt with History
        $history = $this->getRecentHistory($userId, 5);
        $systemPrompt = "You are a helpful assistant for this website. Use the following context to answer the user's question. If the answer is not in the context, say you don't know but try to be helpful based on general knowledge if appropriate.\n\nFormatting: Use Markdown to make your answers attractive and easy to read. Use headings (###), bold text, and bulleted lists where appropriate.\n\nContext:\n$context";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt]
        ];

        // Add history (reversed to be chronological)
        $history = array_reverse($history);
        foreach ($history as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['message']];
        }

        // Add current question
        $messages[] = ['role' => 'user', 'content' => $message];

        // 5. Stream Response from OpenAI
        // We capture the full response to save it later
        $fullResponse = "";
        
        // Use a callback/capture method if OpenAIService supports it, or just manual streaming here.
        // Since I implemented streamChat to just ECHO, I need to intercept it to save to DB.
        // This is tricky with direct echo.
        // I will reimplement stream logic here or modify OpenAIService to return chunks so I can aggregate.
        // FOR NOW: I will start output buffering, but flush it to client, while keeping a copy? 
        // No, you can't flush to client and keep in buffer easily without custom handling.
        // I will assume OpenAIService::streamChat just echos.
        // To save to DB, I'll need to use the `stream` parameter in Guzzle or custom loop.
        // Let's implement the loop here manually to have control.
        
        $this->streamAndSave($messages, $userId);
    }

    private function streamAndSave(array $messages, int $userId)
    {
        // Use GeminiService to stream chat
        // We need to wrap the output in OpenAI-compatible SSE format for the frontend
        
        $fullResponse = $this->ai->streamChat($messages, function($chunk) {
            $data = [
                'choices' => [
                    [
                        'delta' => [
                            'content' => $chunk
                        ]
                    ]
                ]
            ];
            
            echo "data: " . json_encode($data) . "\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        });

        // 6. Save Assistant Response
        if (!empty($fullResponse)) {
            $this->saveMessage($userId, 'assistant', $fullResponse);
        }
    }

    private function findRelevantChunks(array $queryVec, int $limit): array
    {
        // 1. Fetch all chunks (naive approach for demo)
        $stmt = $this->db->query("SELECT id, content, embedding FROM document_chunks");
        $chunks = $stmt->fetchAll();
        
        $scored = [];
        foreach ($chunks as $chunk) {
            $vec = json_decode($chunk['embedding'], true);
            if (!is_array($vec)) continue;
            
            $score = $this->cosineSimilarity($queryVec, $vec);
            $scored[] = ['content' => $chunk['content'], 'score' => $score];
        }

        // 2. Sort DESC
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score']);

        // 3. Slice
        return array_slice($scored, 0, $limit);
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        // Assuming normalized vectors (OpenAI embeddings are unit length)
        // Dot product is enough
        $dot = 0.0;
        $count = count($vecA);
        // Optimization: loop count is min(countA, countB) but should be equal
        for ($i = 0; $i < $count; $i++) {
            $dot += $vecA[$i] * ($vecB[$i] ?? 0);
        }
        return $dot;
    }

    public function getHistory()
    {
        $userId = Auth::id();
        $history = $this->getRecentHistory($userId, 30);
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'history' => array_reverse($history)]);
    }

    private function saveMessage(int $userId, string $role, string $msg)
    {
        $stmt = $this->db->prepare("INSERT INTO chat_history (user_id, role, message) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $role, $msg]);
    }

    private function getRecentHistory(int $userId, int $limit): array
    {
        $stmt = $this->db->prepare("SELECT role, message FROM chat_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
