<?php

namespace App\Services;

class OpenAIService
{
    private string $apiKey;
    private string $embedModel;
    private string $chatModel;

    public function __construct()
    {
        $this->apiKey = $_ENV['OPENAI_API_KEY'];
        $this->embedModel = $_ENV['EMBEDDING_MODEL'] ?? 'text-embedding-3-small';
        $this->chatModel = $_ENV['OPENAI_MODEL'] ?? 'gpt-3.5-turbo';
    }

    public function getEmbedding(string $text): array
    {
        $url = 'https://api.openai.com/v1/embeddings';
        $data = [
            'model' => $this->embedModel,
            'input' => str_replace("\n", " ", $text),
        ];

        $response = $this->request($url, $data);
        return $response['data'][0]['embedding'] ?? [];
    }

    public function getChatCompletionStream(array $messages): \Generator
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => true,
        ];

        return $this->streamRequest($url, $data);
    }

    private function request(string $url, array $data): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
             throw new \Exception('Curl error: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        $json = json_decode($result, true);
        if (isset($json['error'])) {
            throw new \Exception('OpenAI Error: ' . $json['error']['message']);
        }

        return $json;
    }

    private function streamRequest(string $url, array $data): \Generator
    {
        $ch = curl_init($url);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Callback for streaming
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
            // Yield the chunk inside the generator loop? 
            // cURL writefunction cannot directly yield.
            // So we need to store data in a buffer or use a different approach for streaming generators in PHP + cURL.
            // A common way is to simply echo the content if we are the direct output, 
            // OR use a stream resource.
            // But here I want to return a Generator.
            // It's easier to handle the simpler "echo immediately" approach in the controller for streaming to client.
            // However, to keep it clean, let's just make this function handle the output to the browser directly?
            // Or use a callback.
            // For now, I will not use a Generator here but rather pass a callback or just standard cURL stream.
            // But to return a Generator from a function that wraps cURL is tricky without blocking.
            // I'll change the signature to `streamChat` which takes a callback.
            echo $chunk; 
            return strlen($chunk);
        });

        // But wait, if I echo here, I can't process it easily if I wanted to (e.g. logging).
        // But the requirement is to stream smoothly to frontend.
        // So I'll just let it echo.
        // However, this method `streamRequest` is supposed to return a Generator? 
        // No, let's break that pattern. `streamChat` will just execute and output directly.
        
        return 0; // Dummy
    }
    
    // Revised Streaming Method
    public function streamChat(array $messages)
    {
        $url = 'https://api.openai.com/v1/chat/completions';
        $data = [
            'model' => $this->chatModel,
            'messages' => $messages,
            'stream' => true,
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // We need to parse SSE format "data: {...}"
        // But to be simple, we can just pass-through the exact OpenAI chunks to the frontend
        // and let the frontend JS parse the SSE. This is much more efficient.
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) {
            echo $chunk;
            if (ob_get_level() > 0) ob_flush();
            flush();
            return strlen($chunk);
        });
        
        curl_exec($ch);
        curl_close($ch);
    }
}
