<?php

namespace App\Telegram\ContentType;

class TipsTelegram extends BaseContentType
{
    public function description(): string
    {
        return 'Tips';
    }

    public function emoji(): string
    {
        return '💡';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

FORMAT OUTPUT: Objek JSON dengan:
- "title": judul tips yang menarik (maks 60 karakter)
- "intro": 1-2 kalimat pembuka
- "tips": array 5 objek tip, masing-masing dengan:
  - "number": angka
  - "emoji": emoji yang relevan
  - "tip": teks tip (1-2 kalimat)
  - "why": penjelasan singkat kenapa ini penting
- "soft_skill": dimensi soft skill mana yang relevan (dari 5 dimensi)
- "cta": ajakan bertindak

Semua teks dalam bahasa Indonesia. Praktis, bisa langsung dipraktikkan, hangat.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten TIPS parenting untuk Telegram tentang: "{$topic}"

Tips ini harus praktis dan bisa langsung dipraktikkan orang tua hari ini.
Hubungkan dengan salah satu dimensi soft skill anak.
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "title": judul tips menarik (maks 60 karakter)
  - "intro": 1-2 kalimat pembuka
  - "tips": array 5 objek tip (masing-masing punya "number", "emoji", "tip", "why")
  - "soft_skill": dimensi soft skill yang relevan
  - "cta": ajakan bertindak
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>💡 TIPS TELEGRAM</b>';
        $parts[] = '';

        // ── JUDUL ──
        if (!empty($r['title'])) {
            $parts[] = '<b>━━━ ' . $r['title'] . ' ━━━</b>';
        }

        // ── INTRO ──
        if (!empty($r['intro'])) {
            $parts[] = '';
            $parts[] = '<b>📝 PEMBUKA:</b>';
            $parts[] = $r['intro'];
        }

        // ── TIPS ──
        if (!empty($r['tips'])) {
            $parts[] = '';
            $parts[] = '<b>💡 TIPS:</b>';
            foreach ($r['tips'] as $tip) {
                $emoji = $tip['emoji'] ?? '•';
                $num = $tip['number'] ?? '';
                $text = $tip['tip'] ?? '';
                $why = $tip['why'] ?? '';
                $parts[] = '';
                $parts[] = "{$emoji} <b>{$num}. {$text}</b>";
                if ($why) $parts[] = "   {$why}";
            }
        }

        // ── SOFT SKILL ──
        if (!empty($r['soft_skill'])) {
            $parts[] = '';
            $parts[] = '<b>🎯 SOFT SKILL:</b> ' . $r['soft_skill'];
        }

        // ── CTA ──
        if (!empty($r['cta'])) {
            $parts[] = '';
            $parts[] = '<b>👆 CTA:</b>';
            $parts[] = $r['cta'];
        }

        return implode("\n", $parts);
    }
}
