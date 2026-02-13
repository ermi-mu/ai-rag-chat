<?php

namespace App\Services;

class WebScraperService
{
    /**
     * Scrape text content from a URL correctly for RAG.
     */
    public function scrape(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AI-RAG-Scraper/1.1');
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $html = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Network Error: $error");
        }

        if ($httpCode >= 400) {
            throw new \Exception("Server returned error code $httpCode");
        }

        if (empty($html)) {
            throw new \Exception("The URL returned no content.");
        }

        return [
            'content' => $this->cleanHtml($html),
            'title' => $this->extractTitle($html, $url)
        ];
    }

    private function extractTitle(string $html, string $url): string
    {
        if (preg_match('/<title>(.*?)<\/title>/is', $html, $match)) {
            return trim($match[1]);
        }
        return parse_url($url, PHP_URL_HOST) ?: $url;
    }

    private function cleanHtml(string $html): string
    {
        try {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            // Use mb_convert_encoding if available to handle charset issues
            $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            $exclude = ['script', 'style', 'nav', 'footer', 'header', 'aside', 'noscript', 'iframe', 'svg', 'button', 'form'];
            foreach ($exclude as $tag) {
                $nodes = $dom->getElementsByTagName($tag);
                while ($nodes->length > 0) {
                    $node = $nodes->item(0);
                    $node->parentNode->removeChild($node);
                }
            }

            $text = $dom->textContent;
        } catch (\Exception $e) {
            // Fallback to strip_tags if DOMDocument fails
            $text = strip_tags($html);
        }

        // Deep cleaning of whitespace and invisible characters
        $text = str_replace(["\r", "\n", "\t"], " ", $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}
