<?php

namespace App\Services\Notification\WhatsApp;

use App\Contracts\WhatsAppProviderInterface;

class TwilioProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message): bool
    {
        $sid = config('langkahkecil.whatsapp.token');
        $url = config('langkahkecil.whatsapp.url');

        if (!$sid || !$url) {
            \Log::warning('[Twilio] Token/URL not configured');
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $to);
        if (str_starts_with($phone, '0')) {
            $phone = '+62' . substr($phone, 1);
        } elseif (!str_starts_with($phone, '+')) {
            $phone = '+' . $phone;
        }

        try {
            $response = \Http::asForm()
                ->timeout(30)
                ->post($url, [
                    'To' => "whatsapp:{$phone}",
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            \Log::warning('[Twilio] Send failed', ['status' => $response->status()]);
            return false;
        } catch (\Throwable $e) {
            \Log::warning('[Twilio] Exception: ' . $e->getMessage());
            return false;
        }
    }
}
