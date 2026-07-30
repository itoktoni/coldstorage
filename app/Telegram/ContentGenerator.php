<?php

namespace App\Telegram;

use App\Services\AiService;
use App\Services\ImageGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContentGenerator
{
    private AiService $ai;
    private ImageGeneratorService $imageService;

    public function __construct(AiService $ai, ImageGeneratorService $imageService)
    {
        $this->ai = $ai;
        $this->imageService = $imageService;
    }

    public function generate(TelegramContentType $type, string $topic, ?string $provider = null, ?string $model = null): array
    {
        $systemPrompt = $type->systemPrompt();
        $userPrompt = $type->userPrompt($topic);

        $result = $this->ai->chat($provider, $model, $systemPrompt, $userPrompt, 0.8, 8000);

        if ($result) {
            return $result;
        }

        $rawContent = $this->getRawResponse($provider, $model, $systemPrompt, $userPrompt);

        Log::warning('[ContentGenerator] JSON parse failed, using raw fallback', [
            'type' => $type->value,
            'raw_length' => strlen($rawContent ?? ''),
        ]);

        if (!empty($rawContent)) {
            return ['_raw' => true, '_text' => $rawContent];
        }

        throw new \RuntimeException("AI gagal generate konten {$type->description()} untuk: {$topic}");
    }

    public function generateBatch(TelegramContentType $type, int $count, ?string $provider = null, ?string $model = null): array
    {
        $pilarsContext = $this->getPilarsContext();
        $systemPrompt = $type->batchSystemPrompt();
        $userPrompt = $type->batchUserPrompt($count, $pilarsContext);

        Log::info('[ContentGenerator] Batch generate', [
            'type' => $type->value,
            'count' => $count,
        ]);

        $result = $this->ai->chat($provider, $model, $systemPrompt, $userPrompt, 0.8, 8000);

        if ($result && isset($result[0])) {
            return $result;
        }

        if ($result && isset($result['topik'])) {
            return [$result];
        }

        $rawContent = $this->getRawResponse($provider, $model, $systemPrompt, $userPrompt);

        if (!empty($rawContent)) {
            $parsed = $this->parseRawArray($rawContent);
            if (!empty($parsed)) {
                return $parsed;
            }
        }

        Log::warning('[ContentGenerator] Batch gagal, fallback');
        return $type->fallbackBatch();
    }

    public function formatBatch(TelegramContentType $type, array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            try {
                $topik = $item['topik'] ?? '';
                if (is_array($topik)) $topik = implode(', ', $topik);
                $topik = cleanText((string) $topik);

                $alasan = $item['alasan'] ?? '';
                if (is_array($alasan)) $alasan = implode(', ', $alasan);
                $alasan = cleanText((string) $alasan);

                $pilar = $item['pilar'] ?? $item['skill'] ?? $item['skills'] ?? $item['soft_skill'] ?? '';
                if (is_array($pilar)) $pilar = implode(', ', $pilar);
                $pilar = cleanText((string) $pilar);

                $konten = $item['konten'] ?? $item['content'] ?? null;

                $message = '';
                if ($konten && is_array($konten)) {
                    $konten = $this->cleanArray($konten);
                    $message = $type->formatOutput($konten);
                }

                $message = cleanText((string) $message);

                $formatted[] = [
                    'topik' => (string) $topik,
                    'alasan' => (string) $alasan,
                    'pilar' => (string) $pilar,
                    'message' => (string) $message,
                ];
            } catch (\Throwable $e) {
                Log::warning('[ContentGenerator] formatBatch item error', [
                    'error' => $e->getMessage(),
                    'item_keys' => array_keys($item),
                ]);
                $formatted[] = [
                    'topik' => (string) ($item['topik'] ?? 'unknown'),
                    'alasan' => '',
                    'pilar' => '',
                    'message' => '',
                ];
            }
        }

        return $formatted;
    }

    private function cleanArray(array $data): array
    {
        $cleaned = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $cleaned[$key] = cleanText($value);
            } elseif (is_array($value)) {
                $cleaned[$key] = $this->cleanArray($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }

    public function generateAndFormat(TelegramContentType $type, string $topic, ?string $provider = null, ?string $model = null): string
    {
        $result = $this->generate($type, $topic, $provider, $model);
        return $this->format($type, $result);
    }

    public function format(TelegramContentType $type, array $result): string
    {
        if (!empty($result['_raw'])) {
            return cleanText($this->formatRaw($result['_text'] ?? ''));
        }

        $cleaned = $this->cleanArray($result);
        return cleanText($type->formatOutput($cleaned));
    }

    public function generateImage(string $topic, string $size = '1024x1024'): ?string
    {
        $prompt = "Warna-warni, ilustrasi ramah anak untuk aplikasi parenting Indonesia. Bentuk bulat lembut, warna pastel (hijau #176c33, oranye #FF8A50, krim #FFF9F3). Tema: {$topic}. Suasana keluarga bahagia, edukatif. Tanpa teks.";

        try {
            $url = $this->imageService->generate($prompt, $size);
            if (!$url) return null;
            return $this->imageService->download($url);
        } catch (\Throwable $e) {
            Log::warning('[ContentGenerator] Generate gambar gagal: ' . $e->getMessage());
            return null;
        }
    }

    private function getPilarsContext(): string
    {
        try {
            $pilars = DB::table('pilars')
                ->select('pilar_key', 'pilar_title', 'pilar_emoji', 'pilar_subtitle')
                ->get();

            if ($pilars->isEmpty()) {
                return '';
            }

            $lines = [];
            foreach ($pilars as $p) {
                $sub = $p->pilar_subtitle ? " — {$p->pilar_subtitle}" : '';
                $lines[] = "{$p->pilar_emoji} {$p->pilar_title} (key: {$p->pilar_key}){$sub}";
            }

            return implode("\n", $lines);
        } catch (\Throwable $e) {
            Log::warning('[ContentGenerator] Gagal ambil pilars: ' . $e->getMessage());
            return '';
        }
    }

    private function getRawResponse(?string $provider, ?string $model, string $systemPrompt, string $userPrompt): ?string
    {
        try {
            $m = $this->ai->getModel($provider, $model);
            $response = $this->ai->client($provider)->post('/chat/completions', [
                'model'       => $m,
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'temperature' => 0.8,
                'max_tokens'  => 8000,
            ]);

            if (!$response->successful()) return null;
            return trim($response->json()['choices'][0]['message']['content'] ?? '');
        } catch (\Throwable $e) {
            Log::warning('[ContentGenerator] Raw request exception: ' . $e->getMessage());
            return null;
        }
    }

    private function parseRawArray(string $text): array
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```+\s*$/i', '', $text);
        $text = trim($text);

        $start = strpos($text, '[');
        $end = strrpos($text, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
            if ($decoded !== null && isset($decoded[0])) {
                return $decoded;
            }
        }

        $decoded = json_decode($text, true);
        if ($decoded !== null && isset($decoded[0])) {
            return $decoded;
        }

        return [];
    }

    private function formatRaw(string $text): string
    {
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
        $text = preg_replace('/\s*```+\s*$/i', '', $text);
        $text = trim($text);

        $decoded = json_decode($text, true);
        if ($decoded !== null) {
            if (isset($decoded['contents'])) {
                $parts = [];
                foreach ($decoded['contents'] as $i => $c) {
                    $num = $i + 1;
                    $title = $c['title'] ?? "Konten #{$num}";
                    $parts[] = "<b>━━━ #{$num} {$title} ━━━</b>";
                    $parts[] = '';

                    if (!empty($c['concept'])) {
                        $parts[] = '<i>' . $c['concept'] . '</i>';
                        $parts[] = '';
                    }

                    if (!empty($c['hook'])) {
                        $hook = $c['hook'];
                        $parts[] = '<b>HOOK (3 detik pertama):</b>';
                        if (is_array($hook)) {
                            if (!empty($hook['visual'])) $parts[] = "Visual: {$hook['visual']}";
                            if (!empty($hook['text'])) $parts[] = "Teks: \"{$hook['text']}\"";
                        } else {
                            $parts[] = $hook;
                        }
                        $parts[] = '';
                    }

                    if (!empty($c['scenes'])) {
                        $parts[] = '── Script Video ──';
                        foreach ($c['scenes'] as $scene) {
                            $sn = $scene['scene_number'] ?? '';
                            $dur = $scene['duration'] ?? '';
                            $parts[] = "<b>Scene {$sn} ({$dur}d)</b>";
                            if (!empty($scene['visual'])) $parts[] = "🎥 {$scene['visual']}";
                            if (!empty($scene['narration'])) $parts[] = "🎤 {$scene['narration']}";
                            if (!empty($scene['text_overlay'])) $parts[] = "📝 {$scene['text_overlay']}";
                            $parts[] = '';
                        }
                    }

                    if (!empty($c['closing'])) $parts[] = '<b>Closing:</b> ' . $c['closing'];
                    if (!empty($c['cta'])) $parts[] = '<b>CTA:</b> ' . $c['cta'];
                    $parts[] = '';
                }

                return implode("\n", $parts);
            }

            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        return $text;
    }
}
