<?php

namespace App\Services\Notification;

use App\Contracts\ChannelInterface;
use Illuminate\Support\Facades\Mail;

class EmailChannel implements ChannelInterface
{
    public function send(string $to, string $message): bool
    {
        try {
            Mail::raw($message, function ($mail) use ($to) {
                $mail->to($to)->subject('Kode Verifikasi ' . config('app.name', 'LangkahKecil'));
            });
            return true;
        } catch (\Exception $e) {
            \Log::warning('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
}
