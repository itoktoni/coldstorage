<?php

namespace App\Services\Notification\WhatsApp;

use App\Contracts\WhatsAppProviderInterface;

class CustomProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message): bool
    {
        $url = config('langkahkecil.whatsapp.url');
        $token = config('langkahkecil.whatsapp.token');

        if (!$url) {
            \Log::warning('[Custom WhatsApp] URL not configured');
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $to);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        try {
            $headers = ['Content-Type' => 'application/json'];
            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            $response = \Http::withHeaders($headers)
                ->timeout(30)
                ->post($url, [
                    'target' => $phone,
                    'to' => $phone,
                    'phone' => $phone,
                    'message' => $message,
                    'text' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            \Log::warning('[Custom WhatsApp] Send failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return false;
        } catch (\Throwable $e) {
            \Log::warning('[Custom WhatsApp] Exception: ' . $e->getMessage());
            return false;
        }
    }
}
