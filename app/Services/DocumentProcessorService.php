<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentProcessorService
{
    public function extractText(string $filePath, string $type): string
    {
        return match (strtolower($type)) {
            'pdf'         => $this->extractPdf($filePath),
            'docx', 'doc' => $this->extractDocx($filePath),
            'xlsx', 'xls' => $this->extractExcel($filePath),
            'csv'         => $this->extractCsv($filePath),
            'txt'         => file_get_contents($filePath),
            'url'         => $this->extractFromUrl($filePath),
            default       => throw new \InvalidArgumentException("Unsupported file type: {$type}"),
        };
    }

    public function extractFromUrl(string $url): string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control'   => 'no-cache',
            ])
                ->timeout(30)
                ->withOptions(['allow_redirects' => true])
                ->get($url);

            if ($response->failed()) {
                throw new \RuntimeException("Gagal mengakses URL: HTTP {$response->status()}");
            }

            $html = $response->body();
            $text = $this->parseHtmlToText($html, $url);

            // Jika konten sangat sedikit, mungkin JS-rendered — log warning
            if (strlen(trim($text)) < 300) {
                Log::warning('Scraped content is very short — site may use JavaScript rendering', [
                    'url'         => $url,
                    'text_length' => strlen(trim($text)),
                ]);
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('URL scraping failed', ['url' => $url, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Gagal mengambil konten dari URL: ' . $e->getMessage());
        }
    }

    private function parseHtmlToText(string $html, string $baseUrl = ''): string
    {
        // Tangkap JSON-LD structured data (sering berisi konten lengkap)
        $structuredData = $this->extractJsonLd($html);

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        // Hapus hanya tag yang benar-benar tidak ada isinya
        $this->removeTags($dom, ['script', 'style', 'iframe', 'noscript', 'svg', 'canvas']);

        $xpath = new \DOMXPath($dom);

        // Coba cari main content dengan banyak selector umum
        $contentSelectors = [
            '//main',
            '//article',
            '//*[@id="content"]',
            '//*[@id="main-content"]',
            '//*[@id="main"]',
            '//*[@id="primary"]',
            '//*[@class="content"]',
            '//*[contains(@class,"main-content")]',
            '//*[contains(@class,"page-content")]',
            '//*[contains(@class,"post-content")]',
            '//*[contains(@class,"entry-content")]',
            '//*[contains(@class,"article-content")]',
            '//*[contains(@class,"container")]',
            '//*[contains(@class,"wrapper")]',
        ];

        $mainNode = null;
        foreach ($contentSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes && $nodes->length > 0) {
                $candidate = $nodes->item(0);
                $candidateText = trim($this->nodeToText($candidate));
                // Terima hanya jika konten cukup panjang (>200 karakter)
                if (strlen($candidateText) > 200) {
                    $mainNode = $candidate;
                    break;
                }
            }
        }

        // Fallback ke seluruh body
        if (! $mainNode) {
            $mainNode = $dom->getElementsByTagName('body')->item(0);
        }

        $text = $mainNode ? $this->nodeToText($mainNode) : strip_tags($html);

        // Jika teks masih sangat pendek, gunakan strip_tags sederhana pada seluruh HTML
        if (strlen(trim($text)) < 200) {
            $text = $this->fallbackStripTags($html);
        }

        // Tambahkan structured data jika ada
        if (! empty($structuredData)) {
            $text = $structuredData . "\n\n" . $text;
        }

        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    private function extractJsonLd(string $html): string
    {
        $texts = [];
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $matches);

        foreach ($matches[1] as $json) {
            try {
                $data = json_decode(trim($json), true);
                if (! $data) {
                    continue;
                }

                $items = isset($data[0]) ? $data : [$data];
                foreach ($items as $item) {
                    $extracted = [];
                    foreach (['name', 'headline', 'description', 'text', 'articleBody'] as $field) {
                        if (! empty($item[$field])) {
                            $extracted[] = $item[$field];
                        }
                    }
                    if (! empty($extracted)) {
                        $texts[] = implode("\n", $extracted);
                    }
                }
            } catch (\Exception $e) {
                // skip invalid JSON
            }
        }

        return implode("\n\n", $texts);
    }

    private function fallbackStripTags(string $html): string
    {
        // Hapus script dan style blocks dulu
        $clean = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $html);
        $clean = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', $clean ?? '', $clean ?? '');

        // Tambahkan newline sebelum block elements
        $clean = preg_replace('/<\/(p|div|h[1-6]|li|tr|br|section|article)[^>]*>/i', "\n", $clean ?? '');
        $clean = strip_tags($clean ?? '');

        // Decode HTML entities
        $clean = html_entity_decode($clean ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $clean ?? '';
    }

    private function removeTags(\DOMDocument $dom, array $tags): void
    {
        foreach ($tags as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            while ($elements->length > 0) {
                $elements->item(0)->parentNode?->removeChild($elements->item(0));
            }
        }
    }

    private function nodeToText(\DOMNode $node): string
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $content = trim($child->nodeValue ?? '');
                if ($content !== '') {
                    $text .= $content . ' ';
                }
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $tag   = strtolower($child->nodeName);
                $inner = $this->nodeToText($child);

                if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])) {
                    $text .= "\n\n" . strtoupper(trim($inner)) . "\n";
                } elseif (in_array($tag, ['p', 'section', 'article', 'blockquote'])) {
                    $text .= "\n" . trim($inner) . "\n";
                } elseif (in_array($tag, ['li'])) {
                    $text .= "\n• " . trim($inner);
                } elseif (in_array($tag, ['td', 'th'])) {
                    $text .= trim($inner) . ' | ';
                } elseif ($tag === 'tr') {
                    $text .= "\n" . trim($inner);
                } elseif ($tag === 'br') {
                    $text .= "\n";
                } elseif (in_array($tag, ['div', 'span', 'a', 'strong', 'em', 'b', 'i', 'label', 'button'])) {
                    $trimmed = trim($inner);
                    if ($trimmed !== '') {
                        $text .= $trimmed . ' ';
                    }
                } else {
                    $text .= $inner;
                }
            }
        }
        return $text;
    }

    public function scrapeMultiplePages(string $startUrl, int $maxPages = 10): array
    {
        $baseParsed = parse_url($startUrl);
        $baseOrigin = ($baseParsed['scheme'] ?? 'https') . '://' . ($baseParsed['host'] ?? '');

        // Coba ambil semua URL dari sitemap terlebih dahulu
        $sitemapUrls = $this->fetchSitemapUrls($baseOrigin, $maxPages);

        $visited = [];
        $results = [];

        // Jika sitemap berhasil dan ada URL, gunakan sitemap sebagai sumber
        if (! empty($sitemapUrls)) {
            $queue = array_slice(array_unique(array_merge([$startUrl], $sitemapUrls)), 0, $maxPages);
            Log::info("Sitemap found, crawling {$maxPages} pages from sitemap", ['total_sitemap_urls' => count($sitemapUrls)]);
        } else {
            $queue = [$startUrl];
            Log::info("No sitemap found, crawling via link discovery");
        }

        while (! empty($queue) && count($visited) < $maxPages) {
            $url = array_shift($queue);
            $url = $this->normalizeUrl($url);

            if (in_array($url, $visited)) {
                continue;
            }

            $visited[] = $url;

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; AI-Chatbot-Scraper/1.0; +https://ai-chatbot.local)',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                    ->timeout(20)
                    ->get($url);

                if ($response->failed()) {
                    Log::warning("HTTP {$response->status()} for URL: {$url}");
                    continue;
                }

                $contentType = $response->header('Content-Type') ?? '';
                if (! str_contains($contentType, 'text/html') && ! str_contains($contentType, 'application/xhtml')) {
                    continue;
                }

                $html = $response->body();
                $text = $this->parseHtmlToText($html, $url);

                if (strlen(trim($text)) > 80) {
                    $results[$url] = $text;
                    Log::info("Scraped page", ['url' => $url, 'text_length' => strlen($text)]);
                }

                // Temukan link baru dari halaman ini jika belum pakai sitemap
                if (empty($sitemapUrls) && count($visited) < $maxPages) {
                    $links = $this->extractLinks($html, $baseOrigin, $baseParsed['path'] ?? '/');
                    foreach ($links as $link) {
                        $normalized = $this->normalizeUrl($link);
                        if (! in_array($normalized, $visited) && ! in_array($normalized, $queue)) {
                            $queue[] = $normalized;
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Failed to scrape page', ['url' => $url, 'error' => $e->getMessage()]);
            }

            // Delay kecil agar tidak membebani server target
            usleep(300_000);
        }

        Log::info("Crawl completed", ['total_scraped' => count($results), 'max_pages' => $maxPages]);

        return $results;
    }

    private function fetchSitemapUrls(string $baseOrigin, int $limit = 200): array
    {
        $sitemapPaths = ['/sitemap.xml', '/sitemap_index.xml', '/sitemap/sitemap.xml', '/sitemap/index.xml'];
        $urls         = [];

        foreach ($sitemapPaths as $path) {
            try {
                $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; AI-Chatbot-Scraper/1.0)'])
                    ->timeout(10)
                    ->get($baseOrigin . $path);

                if ($response->failed()) {
                    continue;
                }

                $xml = $response->body();
                $parsed = $this->parseSitemap($xml, $baseOrigin);

                if (! empty($parsed)) {
                    $urls = array_merge($urls, $parsed);
                    Log::info("Sitemap found at {$path}", ['url_count' => count($parsed)]);
                    break;
                }
            } catch (\Exception $e) {
                // silently skip
            }
        }

        return array_unique(array_slice($urls, 0, $limit));
    }

    private function parseSitemap(string $xml, string $baseOrigin): array
    {
        $urls = [];

        try {
            $dom = new \DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadXML($xml);
            libxml_clear_errors();

            // sitemap_index → ambil child sitemaps
            $sitemapTags = $dom->getElementsByTagName('sitemap');
            if ($sitemapTags->length > 0) {
                foreach ($sitemapTags as $sitemap) {
                    $loc = $sitemap->getElementsByTagName('loc')->item(0)?->textContent;
                    if ($loc) {
                        try {
                            $response = Http::timeout(10)->get(trim($loc));
                            if ($response->successful()) {
                                $childUrls = $this->parseSitemap($response->body(), $baseOrigin);
                                $urls      = array_merge($urls, $childUrls);
                            }
                        } catch (\Exception $e) {
                            // skip child sitemap error
                        }
                    }
                }
                return $urls;
            }

            // sitemap biasa → ambil <loc> tags
            foreach ($dom->getElementsByTagName('url') as $urlNode) {
                $loc = $urlNode->getElementsByTagName('loc')->item(0)?->textContent;
                if ($loc) {
                    $loc = trim($loc);
                    // Hanya ambil URL dari domain yang sama
                    if (str_starts_with($loc, $baseOrigin) || str_starts_with($loc, '/')) {
                        $urls[] = str_starts_with($loc, '/') ? $baseOrigin . $loc : $loc;
                    }
                }
            }
        } catch (\Exception $e) {
            // bukan XML valid, abaikan
        }

        return $urls;
    }

    private function normalizeUrl(string $url): string
    {
        // Hilangkan fragment (#...)
        $url = preg_replace('/#.*$/', '', $url);
        // Hilangkan trailing slash kecuali root
        $parsed = parse_url($url);
        $path   = rtrim($parsed['path'] ?? '', '/') ?: '/';
        $query  = isset($parsed['query']) ? '?' . $parsed['query'] : '';
        return ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? '') . $path . $query;
    }

    private function extractLinks(string $html, string $baseOrigin, string $basePath): array
    {
        $skipExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
                           'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'rar',
                           'mp4', 'mp3', 'avi', 'css', 'js', 'woff', 'woff2', 'ttf'];

        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        $links = [];
        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim($anchor->getAttribute('href') ?? '');

            if (empty($href)
                || str_starts_with($href, '#')
                || str_starts_with($href, 'mailto:')
                || str_starts_with($href, 'tel:')
                || str_starts_with($href, 'javascript:')
            ) {
                continue;
            }

            // Resolusi URL relatif → absolut
            if (str_starts_with($href, 'http://') || str_starts_with($href, 'https://')) {
                $absolute = $href;
            } elseif (str_starts_with($href, '//')) {
                $absolute = 'https:' . $href;
            } elseif (str_starts_with($href, '/')) {
                $absolute = $baseOrigin . $href;
            } else {
                $absolute = $baseOrigin . '/' . ltrim($href, './');
            }

            // Hanya domain yang sama
            if (! str_starts_with($absolute, $baseOrigin)) {
                continue;
            }

            // Skip file binary/media
            $ext = strtolower(pathinfo(parse_url($absolute, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (in_array($ext, $skipExtensions)) {
                continue;
            }

            $links[] = $absolute;
        }

        return array_unique($links);
    }

    private function extractPdf(string $filePath): string
    {
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdf    = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::error('PDF extraction failed', ['path' => $filePath, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Gagal membaca file PDF: ' . $e->getMessage());
        }
    }

    private function extractDocx(string $filePath): string
    {
        try {
            $phpWord  = \PhpOffice\PhpWord\IOFactory::load($filePath);
            $sections = $phpWord->getSections();
            $text     = '';

            foreach ($sections as $section) {
                foreach ($section->getElements() as $element) {
                    $text .= $this->extractWordElement($element);
                }
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::error('DOCX extraction failed', ['path' => $filePath, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Gagal membaca file DOCX: ' . $e->getMessage());
        }
    }

    private function extractWordElement(mixed $element): string
    {
        $text = '';

        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $child->getText() . ' ';
                }
            }
            $text .= "\n";
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text .= $element->getText() . ' ';
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->extractWordElement($cellElement) . ' | ';
                    }
                }
                $text .= "\n";
            }
        }

        return $text;
    }

    private function extractExcel(string $filePath): string
    {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
            $text        = '';

            foreach ($spreadsheet->getAllSheets() as $sheet) {
                $text .= "Sheet: {$sheet->getTitle()}\n";
                foreach ($sheet->toArray() as $row) {
                    $text .= implode(' | ', array_filter($row, fn ($cell) => $cell !== null && $cell !== '')) . "\n";
                }
                $text .= "\n";
            }

            return trim($text);
        } catch (\Exception $e) {
            Log::error('Excel extraction failed', ['path' => $filePath, 'error' => $e->getMessage()]);
            throw new \RuntimeException('Gagal membaca file Excel: ' . $e->getMessage());
        }
    }

    private function extractCsv(string $filePath): string
    {
        try {
            $text   = '';
            $handle = fopen($filePath, 'r');

            while (($row = fgetcsv($handle)) !== false) {
                $text .= implode(' | ', array_filter($row)) . "\n";
            }

            fclose($handle);
            return trim($text);
        } catch (\Exception $e) {
            throw new \RuntimeException('Gagal membaca file CSV: ' . $e->getMessage());
        }
    }

    public function chunkText(string $text, int $chunkSize = 500, int $overlap = 50): array
    {
        $text   = preg_replace('/\s+/', ' ', trim($text));
        $words  = explode(' ', $text);
        $chunks = [];
        $i      = 0;

        while ($i < count($words)) {
            $chunk    = implode(' ', array_slice($words, $i, $chunkSize));
            $chunks[] = trim($chunk);
            $i       += ($chunkSize - $overlap);
        }

        return array_filter($chunks, fn ($c) => strlen($c) > 50);
    }
}
