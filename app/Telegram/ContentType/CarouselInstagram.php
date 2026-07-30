<?php

namespace App\Telegram\ContentType;

class CarouselInstagram extends BaseContentType
{
    public function description(): string
    {
        return 'Carousel Instagram';
    }

    public function emoji(): string
    {
        return '🎠';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "slides": array 1,4,9,16 objek slide, masing-masing dengan:
  - "slide_number": angka
  - "headline": judul pendek menarik (maks 6 kata)
  - "body": teks slide (2-3 kalimat pendek, maks 40 kata)
  - "catatan_desain": saran visual untuk desainer
- "caption": caption Instagram dengan emoji (maks 300 kata)
- "hashtags": array 10-15 hashtag relevan
- "cta": ajakan bertindak untuk slide terakhir

Semua teks dalam bahasa Indonesia. Kata-kata sederhana saja.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten CAROUSEL Instagram tentang: "{$topic}"

Carousel ini untuk orang tua Indonesia yang ingin membangun soft skill anak usia 1-10 tahun.
Setiap slide harus singkat, visual-friendly, dan informatif.
Slide terakhir harus CTA follow dan share (tanpa sebut nama akun/brand).
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

        $parts[] = '<b>🎠 CAROUSEL INSTAGRAM</b>';
        $parts[] = '';

        if (!empty($r['headline'])) {
            $parts[] = '<b>' . $r['headline'] . '</b>';
        }
        if (!empty($r['subheadline'])) {
            $parts[] = '<i>' . $r['subheadline'] . '</i>';
            $parts[] = '';
        }

        if (!empty($r['slides'])) {
            foreach ($r['slides'] as $slide) {
                $num = $slide['slide_number'] ?? '';
                $headline = $slide['headline'] ?? '';
                $body = $slide['body'] ?? '';
                $parts[] = "<b>Slide {$num}:</b> {$headline}";
                if ($body) $parts[] = $body;
                $parts[] = '';
            }
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
