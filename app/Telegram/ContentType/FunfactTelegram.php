<?php

namespace App\Telegram\ContentType;

class FunfactTelegram extends BaseContentType
{
    public function description(): string
    {
        return 'Fun Fact';
    }

    public function emoji(): string
    {
        return '🧠';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "headline": judul yang bikin penasaran (maks 60 karakter)
- "fakta": fakta mengejutkan (2-3 kalimat dengan data spesifik)
- "penjelasan": kenapa ini penting untuk orang tua (2-3 kalimat)
- "tips_praktis": bagaimana menerapkan pengetahuan ini (2-3 kalimat)
- "kaitan": bagaimana Kepompong berkaitan dengan fakta ini
- "hashtags": array 3-5 hashtag

Semua teks dalam bahasa Indonesia. Menarik, edukatif, mudah dipahami.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten FUN FACT untuk Telegram tentang: "{$topic}"

Fakta ini harus mengejutkan, relevan dengan parenting, dan mudah dipahami.
Hubungkan dengan solusi yang ditawarkan Kepompong.
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "headline": judul bikin penasaran (maks 60 karakter)
  - "fakta": fakta mengejutkan (2-3 kalimat)
  - "penjelasan": kenapa penting untuk orang tua
  - "tips_praktis": bagaimana menerapkan
  - "kaitan": hubungan dengan Kepompong
  - "hashtags": array 3-5 hashtag
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>🧠 FUN FACT TELEGRAM</b>';
        $parts[] = '';

        if (!empty($r['headline'])) {
            $parts[] = '<b>' . $r['headline'] . '</b>';
            $parts[] = '';
        }

        if (!empty($r['fakta'])) {
            $parts[] = '<b>Fakta:</b> ' . $r['fakta'];
        }

        if (!empty($r['penjelasan'])) {
            $parts[] = $r['penjelasan'];
        }

        if (!empty($r['tips_praktis'])) {
            $parts[] = '';
            $parts[] = '<b>Tips:</b> ' . $r['tips_praktis'];
        }

        if (!empty($r['kaitan'])) {
            $parts[] = '';
            $parts[] = $r['kaitan'];
        }

        if (!empty($r['hashtags'])) {
            $parts[] = '';
            $parts[] = $this->formatHashtags($r['hashtags']);
        }

        return implode("\n", $parts);
    }
}
