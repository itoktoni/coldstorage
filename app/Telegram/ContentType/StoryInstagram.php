<?php

namespace App\Telegram\ContentType;

class StoryInstagram extends BaseContentType
{
    public function description(): string
    {
        return 'Story Instagram';
    }

    public function emoji(): string
    {
        return '📱';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "slides": array 3-5 objek story, masing-masing dengan:
  - "slide_number": angka
  - "tipe": "tip" | "pertanyaan" | "polling" | "hitung_mundur" | "swipe_up"
  - "text": teks utama (maks 20 kata per slide)
  - "saran_stiker": saran elemen interaktif/stiker
- "cta": slide terakhir CTA (swipe up / link sticker)

Semua teks dalam bahasa Indonesia. Santai, seru, cepat dibaca.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten STORY Instagram tentang: "{$topic}"

Story ini untuk engagement cepat dengan orang tua.
Gunakan interactive elements (poll, question, countdown).
Setiap slide harus bisa dibaca dalam 3-5 detik.
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

        $parts[] = '<b>📱 STORY INSTAGRAM</b>';
        $parts[] = '';

        if (!empty($r['slides'])) {
            foreach ($r['slides'] as $slide) {
                $num = $slide['slide_number'] ?? '';
                $tipe = $slide['tipe'] ?? '';
                $text = $slide['text'] ?? '';
                $stiker = $slide['saran_stiker'] ?? '';

                $parts[] = "<b>Story {$num} ({$tipe}):</b> {$text}";
                if ($stiker) $parts[] = "Stiker: {$stiker}";
                $parts[] = '';
            }
        }

        if (!empty($r['cta'])) {
            $parts[] = '<b>CTA:</b> ' . $r['cta'];
        }

        return implode("\n", $parts);
    }
}
