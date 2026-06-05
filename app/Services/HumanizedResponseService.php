<?php

namespace App\Services;

class HumanizedResponseService
{
    private const MAX_BUBBLE_CHARS = 280;

    /**
     * @param  array<string, mixed>  $humanizeSettings
     * @return array{content: string, chunks: string[]}
     */
    public function process(string $raw, array $humanizeSettings, bool $active): array
    {
        $text = trim($raw);

        if ($text === '') {
            return ['content' => '', 'chunks' => ['']];
        }

        if (! $active) {
            return ['content' => $text, 'chunks' => [$text]];
        }

        if ($humanizeSettings['avoid_markdown'] ?? true) {
            $text = $this->stripMarkdown($text);
        }

        $chunks = ($humanizeSettings['split_bubbles'] ?? true)
            ? $this->splitIntoChunks($text)
            : [$text];

        $chunks = array_values(array_filter(
            array_map('trim', $chunks),
            fn ($c) => $c !== ''
        ));

        if (empty($chunks)) {
            $chunks = [$text];
        }

        return [
            'content' => implode("\n\n---\n\n", $chunks),
            'chunks' => $chunks,
        ];
    }

    private function stripMarkdown(string $text): string
    {
        $text = preg_replace('/^#{1,6}\s+/m', '', $text) ?? $text;
        $text = preg_replace('/\*\*(.*?)\*\*/s', '$1', $text) ?? $text;
        $text = preg_replace('/\*(.*?)\*/s', '$1', $text) ?? $text;
        $text = preg_replace('/^[\-\*]\s+/m', '', $text) ?? $text;
        $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return string[]
     */
    private function splitIntoChunks(string $text): array
    {
        if (str_contains($text, '---')) {
            $parts = preg_split('/\s*---\s*/', $text) ?: [$text];
            $chunks = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $chunks = array_merge($chunks, $this->splitOversized($part));
            }

            return $chunks ?: [$text];
        }

        return $this->splitOversized($text);
    }

    /**
     * @return string[]
     */
    private function splitOversized(string $text): array
    {
        if (mb_strlen($text) <= self::MAX_BUBBLE_CHARS) {
            return [$text];
        }

        $paragraphs = preg_split('/\n\s*\n/', $text) ?: [$text];
        $chunks = [];

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            if (mb_strlen($paragraph) <= self::MAX_BUBBLE_CHARS) {
                $chunks[] = $paragraph;
                continue;
            }

            $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph) ?: [$paragraph];
            $buffer = '';

            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if ($sentence === '') {
                    continue;
                }

                $candidate = $buffer === '' ? $sentence : $buffer . ' ' . $sentence;

                if (mb_strlen($candidate) <= self::MAX_BUBBLE_CHARS) {
                    $buffer = $candidate;
                } else {
                    if ($buffer !== '') {
                        $chunks[] = $buffer;
                    }
                    $buffer = mb_strlen($sentence) > self::MAX_BUBBLE_CHARS
                        ? mb_substr($sentence, 0, self::MAX_BUBBLE_CHARS)
                        : $sentence;
                }
            }

            if ($buffer !== '') {
                $chunks[] = $buffer;
            }
        }

        return $chunks ?: [$text];
    }
}
