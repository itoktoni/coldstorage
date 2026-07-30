<?php

namespace App\Telegram;

use App\Concerns\EnumTrait;
use App\Telegram\ContentType\BaseContentType;
use App\Telegram\ContentType\CarouselInstagram;
use App\Telegram\ContentType\ReelsInstagram;
use App\Telegram\ContentType\FeedInstagram;
use App\Telegram\ContentType\StoryInstagram;
use App\Telegram\ContentType\ArticleTelegram;
use App\Telegram\ContentType\TipsTelegram;
use App\Telegram\ContentType\PromoTelegram;
use App\Telegram\ContentType\FunfactTelegram;
use App\Telegram\ContentType\ThreadsPost;
use App\Telegram\ContentType\TiktokScript;
use App\Telegram\ContentType\FacebookPost;

enum TelegramContentType: string
{
    use EnumTrait;

    case CAROUSEL_INSTAGRAM = 'carousel_instagram';
    case REELS_INSTAGRAM = 'reels_instagram';
    case FEED_INSTAGRAM = 'feed_instagram';
    case STORY_INSTAGRAM = 'story_instagram';
    case THREADS_POST = 'threads_post';
    case TIKTOK_SCRIPT = 'tiktok_script';
    case FACEBOOK_POST = 'facebook_post';
    case ARTICLE_TELEGRAM = 'article_telegram';
    case TIPS_TELEGRAM = 'tips_telegram';
    case PROMO_TELEGRAM = 'promo_telegram';
    case FUNFACT_TELEGRAM = 'funfact_telegram';

    public function handler(): BaseContentType
    {
        return match ($this) {
            self::CAROUSEL_INSTAGRAM => new CarouselInstagram(),
            self::REELS_INSTAGRAM => new ReelsInstagram(),
            self::FEED_INSTAGRAM => new FeedInstagram(),
            self::STORY_INSTAGRAM => new StoryInstagram(),
            self::ARTICLE_TELEGRAM => new ArticleTelegram(),
            self::TIPS_TELEGRAM => new TipsTelegram(),
            self::PROMO_TELEGRAM => new PromoTelegram(),
            self::FUNFACT_TELEGRAM => new FunfactTelegram(),
            self::THREADS_POST => new ThreadsPost(),
            self::TIKTOK_SCRIPT => new TiktokScript(),
            self::FACEBOOK_POST => new FacebookPost(),
        };
    }

    public function description(): string
    {
        return $this->handler()->description();
    }

    public function emoji(): string
    {
        return $this->handler()->emoji();
    }

    public function platform(): string
    {
        return match ($this) {
            self::CAROUSEL_INSTAGRAM, self::REELS_INSTAGRAM, self::FEED_INSTAGRAM, self::STORY_INSTAGRAM => 'instagram',
            self::TIKTOK_SCRIPT => 'tiktok',
            self::THREADS_POST => 'threads',
            self::FACEBOOK_POST => 'facebook',
            self::ARTICLE_TELEGRAM, self::TIPS_TELEGRAM, self::PROMO_TELEGRAM, self::FUNFACT_TELEGRAM => 'telegram',
        };
    }

    public function chatId(): ?string
    {
        return config('langkahkecil.telegram.group_id') ?: config('langkahkecil.telegram.chat_id');
    }

    public function threadId(): ?int
    {
        $platform = $this->platform();
        $threadId = config("langkahkecil.telegram.threads.{$platform}");

        return $threadId ? (int) $threadId : null;
    }

    public function systemPrompt(): string
    {
        return $this->handler()->systemPrompt();
    }

    public function userPrompt(string $topic): string
    {
        return $this->handler()->userPrompt($topic);
    }

    public function formatOutput(array $result): string
    {
        return $this->handler()->formatOutput($result);
    }

    public function batchSystemPrompt(): string
    {
        return $this->handler()->systemPrompt();
    }

    public function batchFormatSpec(): string
    {
        return $this->handler()->batchFormatSpec();
    }

    public function batchUserPrompt(int $count, ?string $pilarsContext = null): string
    {
        $niche = config('langkahkecil.telegram.niche', 'melatih soft skill dan life skill anak usia 1-10 tahun');

        $pilarsBlock = '';
        if ($pilarsContext) {
            $pilarsBlock = <<<PILARS

SOFT SKILL & LIFE SKILL YANG TERSEDIA DI APLIKASI:
{$pilarsContext}

Setiap konten HARUS terkait dengan minimal 1 pilar di atas. Sebutkan pilar mana yang relevan di field "pilar".
PILARS;
        }

        $formatSpec = $this->handler()->batchFormatSpec();

        return <<<PROMPT
Buatkan {$count} konten {$this->description()} sekaligus lengkap dengan topik dan isi kontennya.

NICHE: "{$niche}"
{$pilarsBlock}
{$formatSpec}

Syarat:
- Setiap topik harus BERBEDA dan UNIK
- Topik relevan dengan kehidupan sehari-hari orang tua Indonesia
- Hindari topik terlalu teknis atau akademik
- Fokus ke hal yang bisa langsung dipraktikkan
- Buat yang relate dan bikin orang tua merasa "wah, pengen coba juga!"
- Konten TIDAK boleh terkesan menjual (softselling)
- TIDAK BOLEH menggurui — showcase keberhasilan, cerita positif, hasil nyata
- Hindari kata: "seharusnya", "harus", "jangan lupa", "penting untuk", "tips yang harus"
- Gunakan kata: "coba deh", "hasilnya?", "bunda bisa lihat", "Si Kecil jadi..."
- Bahasa Indonesia sederhana, TANPA kata bahasa asing
- WAJIB isi field "pilar" dengan nama pilar dari daftar di atas

Output dalam format JSON array.
PROMPT;
    }

    public function fallbackBatch(): array
    {
        return [
            ['topik' => 'Melatih anak berani bicara', 'alasan' => 'Banyak orang tua khawatir anaknya pemalu', 'konten' => null],
            ['topik' => 'Mengajarkan empati pada anak', 'alasan' => 'Empati dasar soft skill penting', 'konten' => null],
            ['topik' => 'Aktivitas melatih kemandirian', 'alasan' => 'Orang tua sering kebanyakan membantu', 'konten' => null],
        ];
    }
}
