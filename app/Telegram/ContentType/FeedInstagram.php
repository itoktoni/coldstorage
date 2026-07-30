<?php

namespace App\Telegram\ContentType;

class FeedInstagram extends BaseContentType
{
    public function description(): string
    {
        return 'Feed Instagram';
    }

    public function emoji(): string
    {
        return '📷';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "headline": judul utama untuk gambar (maks 8 kata)
- "subheadline": teks pendukung (maks 15 kata)
- "body": 3-4 poin informasi penting
- "catatan_desain": saran gaya visual
- "caption": caption Instagram dengan gaya bercerita (maks 300 kata)
- "hashtags": array 10-15 hashtag

Semua teks dalam bahasa Indonesia. Sederhana, hangat, menarik perhatian.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten FEED Instagram (single post) tentang: "{$topic}"

Post ini untuk edukasi orang tua tentang pentingnya aktivitas offline untuk anak.
Desain harus eye-catching, caption harus storytelling.
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "headline": judul utama (maks 8 kata)
  - "subheadline": teks pendukung (maks 15 kata)
  - "body": 3-4 poin informasi penting
  - "caption": caption Instagram dengan emoji (maks 300 kata)
  - "hashtags": array 10-15 hashtag
  - "cta": ajakan bertindak
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>📷 FEED INSTAGRAM</b>';
        $parts[] = '';

        if (!empty($r['headline'])) {
            $parts[] = '<b>' . $r['headline'] . '</b>';
        }
        if (!empty($r['subheadline'])) {
            $parts[] = '<i>' . $r['subheadline'] . '</i>';
            $parts[] = '';
        }

        if (!empty($r['body'])) {
            if (is_array($r['body'])) {
                foreach ($r['body'] as $point) {
                    $parts[] = "• {$point}";
                }
            } else {
                $parts[] = $r['body'];
            }
            $parts[] = '';
        }

        if (!empty($r['caption'])) {
            $parts[] = '── Caption ──';
            $parts[] = $r['caption'];
        }

        if (!empty($r['hashtags'])) {
            $parts[] = '';
            $parts[] = $this->formatHashtags($r['hashtags']);
        }

        return implode("\n", $parts);
    }
}
