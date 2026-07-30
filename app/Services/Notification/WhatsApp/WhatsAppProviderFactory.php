<?php

namespace App\Services\Notification\WhatsApp;

use App\Contracts\WhatsAppProviderInterface;

class WhatsAppProviderFactory
{
    public static function make(): WhatsAppProviderInterface
    {
        $provider = config('langkahkecil.whatsapp.provider', 'log');

        return match ($provider) {
            'fonnte' => new FonnteProvider(),
            'twilio' => new TwilioProvider(),
            'custom' => new CustomProvider(),
            default => new LogProvider(),
        };
    }
}
