<?php

namespace App\Services;

class DocumentService
{
    public function extractText(string $filePath, string $mimeType): string
    {
        if ($mimeType === 'application/pdf') {
            if (!class_exists(\Smalot\PdfParser\Parser::class)) {
                throw new \Exception("PDF Parser not installed. Run 'composer install'.");
            }
            $parser = new \Smalot\PdfParser\Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } elseif ($mimeType === 'text/plain') {
            return file_get_contents($filePath);
        }

        throw new \Exception("Unsupported file type: $mimeType");
    }

    public function chunkText(string $text, int $maxTokens = 800): array
    {
        // Simple chunking by character count approximation (1 token ~= 4 chars)
        // Better implementation would use a proper tokenizer, but this is sufficient for a basic PHP RAG.
        $chunkSize = $maxTokens * 4;
        $chunks = [];
        $length = strlen($text);
        
        for ($i = 0; $i < $length; $i += $chunkSize) {
            // Try to find a sentence break near the chunk size to avoid cutting words
            $chunk = substr($text, $i, $chunkSize);
            $chunks[] = $chunk;
        }

        return $chunks;
    }
}
