<?php

namespace App\Services\Notification\WhatsApp;

use App\Contracts\WhatsAppProviderInterface;

class FonnteProvider implements WhatsAppProviderInterface
{
    public function send(string $to, string $message): bool
    {
        $token = config('langkahkecil.whatsapp.token');
        $url = config('langkahkecil.whatsapp.url', 'https://api.fonnte.com/send');

        if (!$token) {
            \Log::warning('[Fonnte] Token not configured');
            return false;
        }

        $phone = preg_replace('/[^0-9]/', '', $to);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        try {
            $response = \Http::withHeaders([
                'Authorization' => $token,
            ])->timeout(30)->post($url, [
                'target' => $phone,
                'message' => $message,
                'countryCode' => '62',
            ]);

            $data = $response->json();

            if (!empty($data['status'])) {
                return true;
            }

            \Log::warning('[Fonnte] Send failed', ['response' => $data, 'to' => $phone]);
            return false;
        } catch (\Throwable $e) {
            \Log::warning('[Fonnte] Exception: ' . $e->getMessage());
            return false;
        }
    }
}
