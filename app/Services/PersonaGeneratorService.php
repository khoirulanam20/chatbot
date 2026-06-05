<?php

namespace App\Services;

use App\Models\Chatbot;

class PersonaGeneratorService
{
    private const VALID_TONES = ['ramah', 'formal', 'profesional', 'santai'];

    public function __construct(
        private SumopodService $sumopod
    ) {}

    /**
     * @return array{role: string, tone: string, instructions: string, restrictions: string, greeting_style: string}
     */
    public function generate(Chatbot $chatbot, string $description): array
    {
        $description = trim($description);
        if ($description === '') {
            throw new \InvalidArgumentException('Deskripsi tidak boleh kosong.');
        }

        $sumopod = $this->sumopod->withTenantSettings(
            $chatbot->tenant?->getAiConfig() ?? []
        );

        $messages = [
            [
                'role' => 'system',
                'content' => 'Kamu adalah asisten yang membuat konfigurasi persona chatbot. '
                    . 'Berdasarkan deskripsi singkat dari user, hasilkan persona dalam Bahasa Indonesia. '
                    . 'Balas HANYA dengan JSON valid tanpa markdown, tanpa penjelasan tambahan. '
                    . 'Struktur JSON wajib: {"role":"...","tone":"ramah|formal|profesional|santai",'
                    . '"instructions":"...","restrictions":"...","greeting_style":"...",'
                    . '"humanize":{"enabled":true,"channels":["whatsapp","web"],'
                    . '"emoji_level":"none|minimal|medium|often","message_length":"short|medium|long",'
                    . '"split_bubbles":true,"pacing_ms":1200,"use_fillers":true,"avoid_markdown":true}}. '
                    . 'Field instructions dan restrictions harus konkret dan actionable.',
            ],
            [
                'role' => 'user',
                'content' => $description,
            ],
        ];

        $result = $sumopod->chatOnce($messages, $chatbot, temperature: 0.4, maxTokens: 1200);
        $parsed = $this->parseJsonContent($result['content']);

        return $this->normalizePersona($parsed);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonContent(string $content): array
    {
        $content = trim($content);

        if (preg_match('/```(?:json)?\s*([\s\S]*?)```/i', $content, $matches)) {
            $content = trim($matches[1]);
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Gagal memparse respons AI. Coba lagi dengan deskripsi yang lebih jelas.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{role: string, tone: string, instructions: string, restrictions: string, greeting_style: string}
     */
    private function normalizePersona(array $data): array
    {
        $tone = strtolower(trim((string) ($data['tone'] ?? 'ramah')));
        if (! in_array($tone, self::VALID_TONES, true)) {
            $tone = 'ramah';
        }

        $humanize = is_array($data['humanize'] ?? null)
            ? array_merge(Chatbot::defaultHumanizeSettings(), $data['humanize'])
            : Chatbot::defaultHumanizeSettings();

        $humanize['enabled'] = (bool) ($humanize['enabled'] ?? true);
        $humanize['split_bubbles'] = (bool) ($humanize['split_bubbles'] ?? true);
        $humanize['use_fillers'] = (bool) ($humanize['use_fillers'] ?? true);
        $humanize['avoid_markdown'] = (bool) ($humanize['avoid_markdown'] ?? true);
        $humanize['pacing_ms'] = max(500, min(3000, (int) ($humanize['pacing_ms'] ?? 1200)));

        $emojiLevels = ['none', 'minimal', 'medium', 'often'];
        if (! in_array($humanize['emoji_level'] ?? '', $emojiLevels, true)) {
            $humanize['emoji_level'] = 'minimal';
        }

        $lengths = ['short', 'medium', 'long'];
        if (! in_array($humanize['message_length'] ?? '', $lengths, true)) {
            $humanize['message_length'] = 'short';
        }

        $channels = array_values(array_filter(
            (array) ($humanize['channels'] ?? ['whatsapp', 'web']),
            fn ($c) => in_array($c, ['whatsapp', 'web'], true)
        ));
        $humanize['channels'] = $channels ?: ['whatsapp', 'web'];

        return [
            'role' => trim((string) ($data['role'] ?? '')),
            'tone' => $tone,
            'instructions' => trim((string) ($data['instructions'] ?? '')),
            'restrictions' => trim((string) ($data['restrictions'] ?? '')),
            'greeting_style' => trim((string) ($data['greeting_style'] ?? '')),
            'humanize' => $humanize,
        ];
    }
}
