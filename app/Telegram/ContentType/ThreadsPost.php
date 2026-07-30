<?php

namespace App\Telegram\ContentType;

class ThreadsPost extends BaseContentType
{
    public function description(): string
    {
        return 'Threads Post';
    }

    public function emoji(): string
    {
        return '🧵';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "threads": array 5-8 objek thread, masing-masing dengan:
  - "number": angka (format 1/N)
  - "text": teks thread (maks 500 karakter)
  - "emoji": emoji relevan di awal
- "cta": thread terakhir dengan CTA

Semua teks dalam bahasa Indonesia. Singkat, tajam, mudah dibagikan. Setiap thread harus bisa berdiri sendiri tapi mengalir sebagai satu cerita.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan THREAD tentang: "{$topic}"

Thread ini untuk viral di kalangan orang tua Indonesia di platform Threads.
Setiap thread harus insightful dan bisa berdiri sendiri.
Thread terakhir harus CTA follow dan share (tanpa sebut nama akun/brand).
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "threads": array 5-8 objek thread (masing-masing punya "number", "text", "emoji")
  - "cta": thread terakhir dengan CTA
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>🧵 THREADS</b>';
        $parts[] = '';

        $items = $r['threads'] ?? $r['tweets'] ?? [];

        if (!empty($items)) {
            $total = count($items);
            foreach ($items as $item) {
                $num = $item['number'] ?? '';
                $emoji = $item['emoji'] ?? '';
                $text = $item['text'] ?? '';
                $parts[] = "<b>{$emoji} {$num}/{$total}</b>";
                $parts[] = $text;
                $parts[] = '';
            }
        }

        if (!empty($r['cta'])) {
            $parts[] = '── CTA ──';
            $parts[] = $r['cta'];
        }

        return implode("\n", $parts);
    }
}
