<?php

namespace App\Jobs;

use App\Telegram\ContentGenerator;
use App\Telegram\TelegramContentType;
use App\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramContentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public ?string $topic = null,
        public ?string $type = null,
        public ?string $message = null,
        public ?string $image = null,
        public ?string $chatId = null,
        public bool $autoTopics = false,
        public int $count = 1,
        public bool $generateImage = false,
        public ?string $provider = null,
        public ?string $model = null,
    ) {}

    public function handle(ContentGenerator $generator, TelegramService $telegram): void
    {
        if (!$telegram->isConfigured()) {
            Log::warning('[SendTelegramContentJob] Telegram belum dikonfigurasi.');
            return;
        }

        $contentType = $this->type ? TelegramContentType::tryFrom($this->type) : TelegramContentType::TIPS_TELEGRAM;

        if ($this->autoTopics) {
            $this->handleBatch($generator, $telegram, $contentType);
            return;
        }

        $this->handleSingle($generator, $telegram, $contentType);
    }

    private function handleBatch(ContentGenerator $generator, TelegramService $telegram, TelegramContentType $contentType): void
    {
        Log::info('[SendTelegramContentJob] Batch generate', [
            'type' => $contentType->value,
            'count' => $this->count,
        ]);

        $items = $generator->generateBatch($contentType, $this->count, $this->provider, $this->model);
        $formatted = $generator->formatBatch($contentType, $items);

        foreach ($formatted as $i => $item) {
            $topic = $item['topik'] ?? '';
            $message = $item['message'] ?? '';

            if (empty($message)) {
                Log::warning('[SendTelegramContentJob] Konten kosong', ['topik' => $topic]);
                continue;
            }

            try {
                $image = $this->image;

                if ($this->generateImage && $topic) {
                    $generatedImage = $generator->generateImage($topic);
                    if ($generatedImage) $image = $generatedImage;
                }

                $result = $telegram->sendContent($message, $image, $this->chatId);
                $messageId = $result['result']['message_id'] ?? null;

                Log::info('[SendTelegramContentJob] Terkirim', [
                    'topik' => $topic,
                    'message_id' => $messageId,
                ]);

                if ($i < count($formatted) - 1) {
                    sleep(2);
                }
            } catch (\Throwable $e) {
                Log::error('[SendTelegramContentJob] Gagal', [
                    'topik' => $topic,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function handleSingle(ContentGenerator $generator, TelegramService $telegram, TelegramContentType $contentType): void
    {
        $message = $this->message;
        $image = $this->image;

        if ($this->topic && $contentType) {
            Log::info('[SendTelegramContentJob] Generating', [
                'topik' => $this->topic,
                'tipe' => $contentType->value,
            ]);

            $message = $generator->generateAndFormat($contentType, $this->topic, $this->provider, $this->model);
        }

        if ($this->generateImage && $this->topic) {
            $generatedImage = $generator->generateImage($this->topic);
            if ($generatedImage) $image = $generatedImage;
        }

        if (empty($message)) {
            Log::warning('[SendTelegramContentJob] Tidak ada pesan.');
            return;
        }

        $result = $telegram->sendContent($message, $image, $this->chatId);
        $messageId = $result['result']['message_id'] ?? null;

        Log::info('[SendTelegramContentJob] Terkirim', ['message_id' => $messageId]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SendTelegramContentJob] Gagal', [
            'error' => $exception->getMessage(),
            'topik' => $this->topic,
            'tipe' => $this->type,
        ]);
    }
}
