<?php

namespace App\Telegram\ContentType;

class ArticleTelegram extends BaseContentType
{
    public function description(): string
    {
        return 'Article';
    }

    public function emoji(): string
    {
        return '📰';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "title": judul artikel (maks 80 karakter)
- "body": teks artikel lengkap menggunakan HTML (<b>, <i>, <br>, <a>). Maks 500 kata.
- "poin_utama": array 3-5 poin penting
- "hashtags": array 3-5 hashtag
- "cta": teks ajakan bertindak

Semua teks dalam bahasa Indonesia. Edukatif, didukung fakta sederhana.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan artikel singkat untuk Telegram tentang: "{$topic}"

Artikel ini untuk orang tua Indonesia yang ingin memahami pentingnya topik ini untuk anak.
Gunakan HTML formatting (<b>, <i>, <br>, <a>).
Maksimal 500 kata. Akhiri dengan CTA follow dan share (tanpa sebut nama akun/brand).
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "title": judul artikel (maks 80 karakter)
  - "body": teks artikel HTML (<b>, <i>, <br>). Maks 500 kata.
  - "poin_utama": array 3-5 poin penting
  - "hashtags": array 3-5 hashtag
  - "cta": ajakan bertindak
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>📰 ARTICLE TELEGRAM</b>';
        $parts[] = '';

        if (!empty($r['title'])) {
            $parts[] = '<b>' . $r['title'] . '</b>';
            $parts[] = '';
        }

        if (!empty($r['body'])) {
            $parts[] = $r['body'];
            $parts[] = '';
        }

        if (!empty($r['poin_utama'])) {
            $parts[] = '<b>Poin Utama:</b>';
            foreach ($r['poin_utama'] as $point) {
                $parts[] = "• {$point}";
            }
            $parts[] = '';
        }

        if (!empty($r['cta'])) {
            $parts[] = $r['cta'];
        }

        if (!empty($r['hashtags'])) {
            $parts[] = '';
            $parts[] = $this->formatHashtags($r['hashtags']);
        }

        return implode("\n", $parts);
    }
}
