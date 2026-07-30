<?php

namespace App\Telegram\ContentType;

class FacebookPost extends BaseContentType
{
    public function description(): string
    {
        return 'Facebook Post';
    }

    public function emoji(): string
    {
        return '👤';
    }

    public function systemPrompt(): string
    {
        return $this->baseSystemPrompt() . <<<'SPEC'

ATURAN PENTING:
- Konten harus SOFTSELLING (bukan hard selling). Fokus ke showcase keberhasilan, cerita positif, momen yang relatable.
- Tujuan adalah BRAND AWARENESS, bukan jualan langsung.
- TIDAK BOLEH menggurui — tunjukkan hasil nyata, bukan nasihat.
- Hindari kata: "seharusnya", "harus", "jangan lupa", "penting untuk"
- Gunakan kata: "coba deh", "hasilnya?", "Si Kecil jadi..."
- Nada: hangat, storytelling, seperti curhat ke teman.
- Facebook cocok untuk cerita panjang, pengalaman pribadi, dan diskusi komunitas.

FORMAT OUTPUT: Objek JSON dengan:
- "headline": judul menarik (maks 80 karakter)
- "body": teks post dengan gaya storytelling (maks 500 kata). Gunakan HTML (<b>, <i>, <br>).
- "poin_utama": array 3-5 poin penting
- "cta": ajakan bertindak (komentar, share, klik link)
- "hashtags": array 5-10 hashtag

Semua teks dalam bahasa Indonesia. Kata-kata sederhana saja.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan konten Facebook Post tentang: "{$topic}"

Post ini untuk orang tua Indonesia yang ingin membangun soft skill anak usia 1-10 tahun.
Gaya bercerita seperti curhat ke teman, hangat, relatable.
Akhiri dengan CTA untuk komentar atau share.
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "headline": judul menarik (maks 80 karakter)
  - "body": teks post storytelling (maks 500 kata, HTML)
  - "poin_utama": array 3-5 poin penting
  - "cta": ajakan bertindak
  - "hashtags": array 5-10 hashtag
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];

        $parts[] = '<b>👤 FACEBOOK POST</b>';
        $parts[] = '';

        // ── JUDUL ──
        if (!empty($r['headline'])) {
            $parts[] = '<b>' . $r['headline'] . '</b>';
        }

        // ── ISI POST ──
        if (!empty($r['body'])) {
            $parts[] = '';
            $parts[] = $r['body'];
        }

        // ── POIN UTAMA ──
        if (!empty($r['poin_utama'])) {
            $parts[] = '';
            $parts[] = '<b>📌 POIN UTAMA:</b>';
            foreach ($r['poin_utama'] as $point) {
                $parts[] = "• {$point}";
            }
        }

        // ── CTA ──
        if (!empty($r['cta'])) {
            $parts[] = '';
            $parts[] = '<b>👆 CTA:</b>';
            $parts[] = $r['cta'];
        }

        // ── HASHTAGS ──
        if (!empty($r['hashtags'])) {
            $parts[] = '';
            $parts[] = '<b>🏷️ HASHTAGS:</b>';
            $parts[] = $this->formatHashtags($r['hashtags']);
        }

        return implode("\n", $parts);
    }
}
