<?php

namespace App\Services\Notification;

use App\Contracts\ChannelInterface;
use App\Services\Notification\WhatsApp\WhatsAppProviderFactory;

class WhatsAppChannel implements ChannelInterface
{
    public function send(string $to, string $message): bool
    {
        $provider = WhatsAppProviderFactory::make();
        return $provider->send($to, $message);
    }
}
