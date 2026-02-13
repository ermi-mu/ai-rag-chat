<?php

namespace App\Services;

class GeminiService
{
    private string $apiKey;
    private string $embedModel;
    private string $chatModel;

    public function __construct()
    {
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? '';
        $this->embedModel = $_ENV['EMBEDDING_MODEL'] ?? 'gemini-embedding-001';
        $this->chatModel = $_ENV['GEMINI_MODEL'] ?? 'gemini-3-flash-preview';
    }

    public function getEmbedding(string $text): array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->embedModel}:embedContent?key={$this->apiKey}";
        
        $data = [
            'content' => [
                'parts' => [
                    ['text' => $text]
                ]
            ]
        ];

        $json = $this->request($url, $data);
        
        if (isset($json['embedding']['values'])) {
            return $json['embedding']['values'];
        }
        
        if (isset($json['error'])) {
             throw new \Exception('Gemini Embedding Error: ' . json_encode($json['error']));
        }
        
        return [];
    }

    public function streamChat(array $messages, callable $onTextChunk): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->chatModel}:streamGenerateContent?key={$this->apiKey}";
        
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = [
                    'parts' => [['text' => $msg['content']]]
                ];
                continue;
            }

            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        $data = [
            'contents' => $contents
        ];

        if ($systemInstruction) {
            $data['systemInstruction'] = $systemInstruction;
        }

        $fullResponseText = '';
        $buffer = '';
        $cursor = 0;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); 
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use ($onTextChunk, &$fullResponseText, &$buffer, &$cursor) {
            $buffer .= $chunk;
            
            // Stateful parsing for "text": "..."
            // Using a regex that handles escaped quotes correctly
            while (preg_match('/"text":\s*"((?:[^"\\\\]|\\\\.)*)"/', $buffer, $match, PREG_OFFSET_CAPTURE, $cursor)) {
                $textData = $match[1][0];
                $fullMatchPos = $match[0][1];
                $fullMatchLen = strlen($match[0][0]);
                
                $decoded = json_decode('"' . $textData . '"');
                if ($decoded !== null) {
                    if ($decoded !== '') {
                        $onTextChunk($decoded);
                        $fullResponseText .= $decoded;
                    }
                }
                
                $cursor = $fullMatchPos + $fullMatchLen;
            }

            // Check for Errors in the stream if we haven't found any text yet
            if (empty($fullResponseText) && preg_match('/"error":\s*({(?:[^{}]|(?R))*})/', $buffer, $errMatch)) {
                 $errJson = json_decode($errMatch[1], true);
                 if ($errJson) {
                     $onTextChunk("Error: " . ($errJson['message'] ?? 'Unknown Gemini Error'));
                     return 0; // Stop curl? or just continue
                 }
            }

            return strlen($chunk);
        });

        curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);
        
        if ($err) {
            throw new \Exception("Curl error: $err");
        }

        return $fullResponseText;
    }

    public function generateResponse(array $messages): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->chatModel}:generateContent?key={$this->apiKey}";
        
        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $systemInstruction = [
                    'parts' => [['text' => $msg['content']]]
                ];
                continue;
            }

            $role = ($msg['role'] === 'assistant') ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg['content']]]
            ];
        }

        $data = [
            'contents' => $contents
        ];

        if ($systemInstruction) {
            $data['systemInstruction'] = $systemInstruction;
        }

        $json = $this->request($url, $data);
        
        if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
            return $json['candidates'][0]['content']['parts'][0]['text'];
        }
        
        return 'Could not generate a summary.';
    }

    private function request(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Curl error: $error");
        }

        return json_decode($result, true) ?? [];
    }
}
