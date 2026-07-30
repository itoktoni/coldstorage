<?php

namespace App\Telegram\ContentType;

class ReelsInstagram extends BaseContentType
{
    public function description(): string
    {
        return 'Reels Instagram';
    }

    public function emoji(): string
    {
        return '🎬';
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
- Konten harus SEDERHANA dan MUDAH diterapkan oleh siapa saja: ibu rumah tangga, ayah/ibu yang bekerja.
- Hook di 3 detik pertama harus SANGAT KUAT — buat penonton berhenti scroll.
- Setiap konten adalah KONSEP dengan catatan produksi lengkap agar kreator bisa langsung syuting.
- Durasi: 30-60 detik total.
- Nada: relatable, hangat, ceria, "wah keren banget!" untuk orang tua.

FORMAT OUTPUT: Objek JSON dengan array "contents" berisi 5 konsep reel. Setiap konsep punya:
- "title": judul menarik bahasa Indonesia (maks 60 karakter, jadi hook text di layar)
- "concept": 2-3 kalimat menjelaskan ide konten dan kenapa cocok untuk brand awareness
- "hook": objek dengan:
  - "text": teks yang diucapkan/ditampilkan di 3 detik pertama (maks 10 kata, harus bikin penasaran atau kaget)
  - "visual": yang dilihat penonton di 3 detik pertama
- "scenes": array 4-6 objek scene, masing-masing dengan:
  - "scene_number": angka
  - "duration": detik (total semua scene = 30-60d)
  - "visual": yang dilihat penonton (sudut kamera, aksi, setting — sederhana untuk syuting di rumah)
  - "narration": teks voiceover atau dialog (bahasa Indonesia percakapan, 1-2 kalimat pendek)
  - "text_overlay": teks yang muncul di layar (pendek, singkat, 3-8 kata)
- "closing": pesan terakhir sebelum CTA (emosional atau menginspirasi, 1-2 kalimat)
- "cta": ajakan bertindak (CTA halus untuk follow/save, bukan jualan)
- "tips_syuting": 2-3 tips praktis untuk syuting di rumah pakai HP saja
- "hashtags": array 10-15 hashtag Indonesia yang relevan

Semua teks dalam bahasa Indonesia. Kata-kata sederhana saja. TANPA kata bahasa asing.
SPEC;
    }

    public function userPrompt(string $topic): string
    {
        return <<<PROMPT
Buatkan 5 ide konten REELS Instagram untuk BRAND AWARENESS tentang: "{$topic}"

Tujuan: brand awareness untuk Kepompong, aplikasi yang membantu orang tua membentuk soft skill & life skill anak usia 1-10 tahun. Cukup 3 detik untuk mendapatkan rekomendasi aktivitas offline.

Syarat penting:
- Konten TIDAK boleh terkesan menjual (softselling, bukan hardselling)
- Ide konten harus SEDERHANA dan MUDAH diaplikasikan oleh konten kreator pemula (ibu rumah tangga, ayah/ibu yang bekerja)
- Hook di 3 detik pertama harus KUAT agar penonton betah menonton sampai akhir
- CTA harus halus (follow, save, share) — bukan CTA jualan
- Durasi 30-60 detik per video
- Buatkan konsep take video lengkap agar kreator bisa langsung praktek
- Judul harus menarik dan bisa dipakai sebagai hook text di layar
PROMPT;
    }

    public function batchFormatSpec(): string
    {
        return <<<'SPEC'

FORMAT OUTPUT: JSON array, setiap objek punya:
- "topik": ide topik singkat (maks 10 kata)
- "alasan": kenapa topik ini menarik (1 kalimat)
- "konten": objek dengan:
  - "title": judul menarik (maks 60 karakter, jadi hook text di layar)
  - "concept": 2-3 kalimat menjelaskan ide konten
  - "hook": objek dengan "text" (maks 10 kata) dan "visual"
  - "scenes": array 4-6 scene, masing-masing dengan "scene_number", "duration", "visual", "narration", "text_overlay"
  - "closing": pesan terakhir sebelum CTA
  - "cta": ajakan bertindak halus (follow/save)
  - "tips_syuting": array 2-3 tips syuting di rumah pakai HP
  - "hashtags": array 10-15 hashtag Indonesia
SPEC;
    }

    public function formatOutput(array $r): string
    {
        $parts = [];
        $contents = $r['contents'] ?? [$r];

        $parts[] = '<b>🎬 REELS INSTAGRAM</b>';
        $parts[] = '';

        foreach ($contents as $i => $c) {
            $num = $i + 1;

            // ── JUDUL ──
            if (!empty($c['title'])) {
                $parts[] = '<b> #' . $num . ' ' . $c['title'] . ' </b>';
            }

            // ── KONSEP ──
            if (!empty($c['concept'])) {
                $parts[] = '';
                $parts[] = '<b>📝 KONSEP:</b>';
                $parts[] = $c['concept'];
            }

            // ── HOOK ──
            if (!empty($c['hook'])) {
                $hook = $c['hook'];
                $parts[] = '';
                $parts[] = '<b>🎣 HOOK (3 detik pertama):</b>';
                if (is_array($hook)) {
                    if (!empty($hook['text'])) $parts[] = 'Teks: "' . $hook['text'] . '"';
                    if (!empty($hook['visual'])) $parts[] = 'Visual: ' . $hook['visual'];
                } else {
                    $parts[] = $hook;
                }
            }

            // ── SCRIPT VIDEO ──
            if (!empty($c['scenes'])) {
                $parts[] = '';
                $parts[] = '<b>🎬 SCRIPT VIDEO:</b>';
                foreach ($c['scenes'] as $scene) {
                    $sn = $scene['scene_number'] ?? '';
                    $dur = $scene['duration'] ?? '';
                    $visual = $scene['visual'] ?? '';
                    $narration = $scene['narration'] ?? '';
                    $overlay = $scene['text_overlay'] ?? '';

                    $parts[] = '';
                    $parts[] = "<b>Scene {$sn} ({$dur}d)</b>";
                    if ($visual) $parts[] = "🎥 {$visual}";
                    if ($narration) $parts[] = "🎤 {$narration}";
                    if ($overlay) $parts[] = "📝 {$overlay}";
                }
            }

            // ── CLOSING ──
            if (!empty($c['closing'])) {
                $parts[] = '';
                $parts[] = '<b>🎬 CLOSING:</b>';
                $parts[] = $c['closing'];
            }

            // ── CTA ──
            if (!empty($c['cta'])) {
                $parts[] = '';
                $parts[] = '<b>👆 CTA:</b>';
                $parts[] = $c['cta'];
            }

            // ── TIPS SYUTING ──
            if (!empty($c['tips_syuting']) || !empty($c['filming_tips'])) {
                $tips = $c['tips_syuting'] ?? $c['filming_tips'] ?? [];
                $parts[] = '';
                $parts[] = '💡 <b>TIPS SYUTING:</b>';
                foreach ((array) $tips as $tip) {
                    $parts[] = "• {$tip}";
                }
            }

            // ── HASHTAGS ──
            if (!empty($c['hashtags'])) {
                $parts[] = '';
                $parts[] = '<b>🏷️ HASHTAGS:</b>';
                $parts[] = $this->formatHashtags($c['hashtags']);
            }

            // ── SPASI ANTAR KONTEN ──
            $parts[] = '';
            $parts[] = '─────────────────';
        }

        return implode("\n", $parts);
    }
}
