<?php

namespace App\Telegram\ContentType;

class PromoTelegram extends BaseContentType
{
    public function description(): string
    {
        return 'Promo';
    }

    public function emoji(): string
    {
        return '🎉';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "headline": judul menarik perhatian dengan emoji (maks 60 karakter)
- "masalah": masalah yang dirasakan orang tua (1-2 kalimat)
- "solusi": bagaimana Kepompong menyelesaikannya (2-3 kalimat)
- "penawaran": detail promo spesifik (diskon, free trial, dll)
- "urgensi": elemen keterbatasan/waktu
- "testimoni": 2 testimoni pendek fiktif tapi relatable dari orang tua
- "cta": ajakan bertindak kuat dengan emoji
- "hashtags": array 5-8 hashtag

Semua teks dalam bahasa Indonesia. Memicu FOMO tapi tetap hangat, tidak memaksa.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten PROMO untuk Telegram tentang: "{$topic}"

Promo ini untuk menarik orang tua mencoba Kepompong.
Buat FOMO tapi tetap hangat dan tidak memaksa.
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "headline": judul menarik dengan emoji
  - "masalah": masalah orang tua (1-2 kalimat)
  - "solusi": bagaimana Kepompong menyelesaikan
  - "penawaran": detail promo
  - "urgensi": elemen keterbatasan
  - "testimoni": array 2 testimoni
  - "cta": ajakan bertindak
  - "hashtags": array 5-8 hashtag
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>🎉 PROMO TELEGRAM</b>';
        $parts[] = '';

        if (!empty($r['headline'])) {
            $parts[] = '<b>' . $r['headline'] . '</b>';
            $parts[] = '';
        }

        if (!empty($r['masalah'])) {
            $parts[] = $r['masalah'];
            $parts[] = '';
        }

        if (!empty($r['solusi'])) {
            $parts[] = $r['solusi'];
            $parts[] = '';
        }

        if (!empty($r['penawaran'])) {
            $parts[] = '<b>' . $r['penawaran'] . '</b>';
            $parts[] = '';
        }

        if (!empty($r['urgensi'])) {
            $parts[] = '<i>' . $r['urgensi'] . '</i>';
            $parts[] = '';
        }

        if (!empty($r['testimoni'])) {
            $parts[] = '── Testimoni ──';
            foreach ($r['testimoni'] as $t) {
                $parts[] = '"' . $t . '"';
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
