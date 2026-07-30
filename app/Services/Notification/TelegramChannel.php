<?php

namespace App\Services\Notification;

use App\Contracts\ChannelInterface;
use App\Telegram\TelegramService;

class TelegramChannel implements ChannelInterface
{
    public function send(string $to, string $message): bool
    {
        $service = new TelegramService();

        if (!$service->isConfigured()) {
            \Log::info('[Telegram] Token/ChatID not configured. Message would be sent to: ' . $to);
            \Log::info('[Telegram] Message: ' . $message);
            return false;
        }

        try {
            $service->sendText($message, $to);
            return true;
        } catch (\Exception $e) {
            \Log::warning('Telegram send failed: ' . $e->getMessage());
            return false;
        }
    }
}
