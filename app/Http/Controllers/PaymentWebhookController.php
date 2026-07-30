<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaidPayment;
use App\Models\Payment;
use App\PaymentStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    private const CHANNEL = 'webhook';

    private const PACKAGE_MAP = [
        'com.gojek.gopay' => 'gopay',
        'com.gojek.gopaymerchant' => 'qris',
        'com.bca' => 'transfer',
        'com.bni' => 'transfer',
        'com.bri' => 'transfer',
        'com.bcadigital' => 'blu',
        'com.mandiri' => 'transfer',
        'com.bsi' => 'transfer',
        'com.dana' => 'dana',
        'com.shopeepay' => 'shopeepay',
        'com.ovo' => 'ovo',
    ];

    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::channel(self::CHANNEL)->info('Webhook received', [
            'payload' => $payload,
            'ip' => $request->ip(),
        ]);

        if (empty($payload['notification']) || empty($payload['app'])) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $packageName = $payload['app']['packageName'] ?? '';
        $appName = $payload['app']['name'] ?? '';
        $title = $payload['notification']['title'] ?? '';
        $text = $payload['notification']['text'] ?? '';
        $device = $payload['device']['id'] ?? '';

        if($device != config('langkahkecil.verification.payment_token'))
        {
            Log::channel(self::CHANNEL)->info('Webhook Not Authorized', [
                'payload' => $payload,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid payload'], 403);
        }

        $metode = $this->resolveMethod($packageName, $title);
        $amount = $this->extractAmount($text);

        Log::channel(self::CHANNEL)->info('Webhook parsed', [
            'package' => $packageName,
            'app_name' => $appName,
            'title' => $title,
            'text' => $text,
            'metode' => $metode,
            'amount' => $amount,
        ]);

        if ($amount <= 0) {
            Log::channel(self::CHANNEL)->warning('Webhook: amount not extracted', [
                'text' => $text,
            ]);

            return response()->json(['message' => 'Amount not detected', 'metode' => $metode], 200);
        }

        $payment = $this->findPendingPayment($amount, $metode);

        if (! $payment) {
            Log::channel(self::CHANNEL)->warning('Webhook: no pending payment found', [
                'amount' => $amount,
                'metode' => $metode,
            ]);

            return response()->json(['message' => 'No pending payment found', 'amount' => $amount, 'metode' => $metode], 200);
        }

        $payment->update([
            'payment_status' => PaymentStatusEnum::PAID->value,
            'payment_metode' => $metode,
            'payment_paid_at' => now(),
            'payment_updated_at' => now(),
        ]);

        ProcessPaidPayment::dispatch($payment->payment_id);

        Log::channel(self::CHANNEL)->info('Webhook: payment settled', [
            'payment_id' => $payment->payment_id,
            'order_code' => $payment->payment_order_code,
            'amount' => $amount,
            'metode' => $metode,
            'user_id' => $payment->payment_id_user,
        ]);

        return response()->json([
            'message' => 'Payment settled',
            'payment_id' => $payment->payment_id,
            'order_code' => $payment->payment_order_code,
            'metode' => $metode,
        ]);
    }

    private function resolveMethod(string $packageName, string $title): string
    {
        return match (true) {
            isset(self::PACKAGE_MAP[$packageName]) => self::PACKAGE_MAP[$packageName],
            str_contains($packageName, 'com.shopeepay.id') && str_contains($title, 'Pembayaran') && str_contains($title, 'diterima') => 'qris',
            str_contains($packageName, 'com.gojek.gopaymerchant') && str_contains($title, 'Pembayaran QRIS statis diterima') => 'qris',
            str_contains($packageName, 'com.gojek.gopay') && str_contains($title, 'Transfer masuk') => 'gopay',
            str_contains($packageName, 'com.gojek.gopay') && str_contains($title, 'GoPay') => 'gopay',
            str_contains($packageName, 'com.bcadigital.blu') && str_contains($title, 'Kamu Menerima Dana Nih!') => 'blu',
            default => 'lainnya',
        };
    }

    private function extractAmount(string $text): int
    {
        if (preg_match('/Rp[\s.]?([\d.]+)/i', $text, $m)) {
            return (int) str_replace('.', '', $m[1]);
        }

        if (preg_match('/([\d.]+)\s*(?:rupiah|IDR)/i', $text, $m)) {
            return (int) str_replace('.', '', $m[1]);
        }

        return 0;
    }

    private function findPendingPayment(int $amount, $metode): ?Payment
    {
        return Payment::where('payment_status', PaymentStatusEnum::PENDING->value)
            ->where('payment_total', $amount)
            ->where('payment_created_at', '<', now())
            ->where('payment_expired_at', '>', now())
            ->orderBy('payment_created_at', 'asc')
            ->first();
    }
}
