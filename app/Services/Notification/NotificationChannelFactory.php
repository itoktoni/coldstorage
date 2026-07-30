<?php

namespace App\Services\Notification;

use App\Contracts\ChannelInterface;

class NotificationChannelFactory
{
    public static function make(string $channel): ChannelInterface
    {
        return match ($channel) {
            'whatsapp' => new WhatsAppChannel(),
            'telegram' => new TelegramChannel(),
            'email' => new EmailChannel(),
            'log' => new LogChannel(),
            default => new LogChannel(),
        };
    }
}
