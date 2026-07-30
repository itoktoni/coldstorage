<?php

namespace App\Services\Notification\WhatsApp;

use App\Contracts\WhatsAppProviderInterface;

class LogProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message): bool
    {
        \Log::info('[WhatsApp Log] To: ' . $to);
        \Log::info('[WhatsApp Log] Message: ' . $message);
        return true;
    }
}
