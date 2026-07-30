<?php

namespace App\Telegram;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private Client $client;
    private string $token;
    private string $chatId;
    private string $apiUrl;

    public function __construct()
    {
        $this->token = (string) config('langkahkecil.telegram.bot_token');
        $this->chatId = (string) config('langkahkecil.telegram.chat_id');
        $this->apiUrl = rtrim((string) config('langkahkecil.telegram.api_url', 'https://api.telegram.org'), '/');
        $this->client = new Client(['timeout' => 30]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->token) && !empty($this->chatId);
    }

    public function sendText(string $message, ?string $chatId = null, array $options = [], ?int $threadId = null): array
    {
        $chatId = $chatId ?? $this->chatId;
        $maxLength = 4000;
        $message = $this->cleanHtml($message);

        if (mb_strlen($message) <= $maxLength) {
            $payload = array_merge([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => false,
            ], $options);

            if ($threadId) {
                $payload['message_thread_id'] = $threadId;
            }

            return $this->callApi('sendMessage', $payload);
        }

        $chunks = $this->splitMessage($message, $maxLength);
        $lastResult = [];

        foreach ($chunks as $i => $chunk) {
            $payload = [
                'chat_id' => $chatId,
                'text' => $chunk,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ];

            if ($threadId) {
                $payload['message_thread_id'] = $threadId;
            }

            if ($i === count($chunks) - 1) {
                $payload = array_merge($payload, $options);
                $payload['disable_web_page_preview'] = false;
            }

            $lastResult = $this->callApi('sendMessage', $payload);

            if ($i < count($chunks) - 1) {
                usleep(300000);
            }
        }

        return $lastResult;
    }

    public function sendPhoto(string $photoPathOrUrl, string $caption = '', ?string $chatId = null, array $options = [], ?int $threadId = null): array
    {
        if (filter_var($photoPathOrUrl, FILTER_VALIDATE_URL)) {
            $payload = array_merge([
                'chat_id' => $chatId ?? $this->chatId,
                'photo' => $photoPathOrUrl,
                'caption' => $caption,
                'parse_mode' => 'HTML',
            ], $options);

            if ($threadId) {
                $payload['message_thread_id'] = $threadId;
            }

            return $this->callApi('sendPhoto', $payload);
        }

        return $this->sendPhotoFile($photoPathOrUrl, $caption, $chatId, $options, $threadId);
    }

    public function sendPhotoFile(string $filePath, string $caption = '', ?string $chatId = null, array $options = [], ?int $threadId = null): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("File not found: {$filePath}");
        }

        $multipart = [
            ['name' => 'chat_id', 'contents' => $chatId ?? $this->chatId],
            ['name' => 'photo', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)],
        ];

        if ($threadId) {
            $multipart[] = ['name' => 'message_thread_id', 'contents' => (string) $threadId];
        }

        if (!empty($caption)) {
            $multipart[] = ['name' => 'caption', 'contents' => $caption];
            $multipart[] = ['name' => 'parse_mode', 'contents' => 'HTML'];
        }

        foreach ($options as $key => $value) {
            $multipart[] = ['name' => $key, 'contents' => $value];
        }

        return $this->callApiMultipart('sendPhoto', $multipart);
    }

    public function sendContent(string $message, ?string $imagePath = null, ?string $chatId = null, array $options = [], ?int $threadId = null): array
    {
        if ($imagePath) {
            return $this->sendPhoto($imagePath, $message, $chatId, $options, $threadId);
        }

        return $this->sendText($message, $chatId, $options, $threadId);
    }

    public function getChatInfo(?string $chatId = null): ?array
    {
        try {
            $response = $this->callApi('getChat', ['chat_id' => $chatId ?? $this->chatId]);
            return $response['result'] ?? null;
        } catch (\Exception $e) {
            Log::warning('[TelegramService] Failed to get chat info: ' . $e->getMessage());
            return null;
        }
    }

    private function callApi(string $method, array $payload): array
    {
        $url = "{$this->apiUrl}/bot{$this->token}/{$method}";

        $response = $this->client->post($url, ['json' => $payload]);
        $body = json_decode($response->getBody()->getContents(), true);

        if (!($body['ok'] ?? false)) {
            throw new \RuntimeException('Telegram API error: ' . ($body['description'] ?? 'Unknown error'));
        }

        return $body;
    }

    private function callApiMultipart(string $method, array $multipart): array
    {
        $url = "{$this->apiUrl}/bot{$this->token}/{$method}";

        $response = $this->client->post($url, ['multipart' => $multipart]);
        $body = json_decode($response->getBody()->getContents(), true);

        if (!($body['ok'] ?? false)) {
            throw new \RuntimeException('Telegram API error: ' . ($body['description'] ?? 'Unknown error'));
        }

        return $body;
    }

    private function splitMessage(string $message, int $maxLength): array
    {
        $chunks = [];
        $lines = explode("\n", $message);
        $current = '';

        foreach ($lines as $line) {
            if (mb_strlen($current) + mb_strlen($line) + 1 > $maxLength) {
                if (!empty($current)) {
                    $chunks[] = trim($current);
                }
                $current = $line;
            } else {
                $current .= ($current ? "\n" : '') . $line;
            }
        }

        if (!empty($current)) {
            $chunks[] = trim($current);
        }

        return $chunks;
    }

    public function cleanHtml(string $message): string
    {
        $allowed = ['b', 'i', 'u', 's', 'code', 'pre', 'a', 'span', 'tg-spoiler', 'blockquote', 'br', 'em', 'strong'];

        $message = preg_replace('/<p[^>]*>/i', '', $message);
        $message = preg_replace('/<\/p>/i', "\n", $message);
        $message = preg_replace('/<div[^>]*>/i', '', $message);
        $message = preg_replace('/<\/div>/i', "\n", $message);
        $message = preg_replace('/<h[1-6][^>]*>/i', '<b>', $message);
        $message = preg_replace('/<\/h[1-6]>/i', '</b>', $message);
        $message = preg_replace('/<li[^>]*>/i', '• ', $message);
        $message = preg_replace('/<\/li>/i', "\n", $message);
        $message = preg_replace('/<\/?[uo]l[^>]*>/i', '', $message);
        $message = preg_replace('/<br\s*\/?>/i', "\n", $message);
        $message = preg_replace('/<hr\s*\/?>/i', "\n─────────────\n", $message);

        $pattern = '/<(\/?)(\w+)[^>]*>/i';
        $message = preg_replace_callback($pattern, function ($matches) use ($allowed) {
            $tag = strtolower($matches[2]);
            if (in_array($tag, $allowed)) {
                return $matches[0];
            }
            return '';
        }, $message);

        $message = preg_replace('/\n{3,}/', "\n\n", $message);

        return trim($message);
    }
}
