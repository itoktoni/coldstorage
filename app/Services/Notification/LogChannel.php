<?php

namespace App\Services\Notification;

use App\Contracts\ChannelInterface;
use Illuminate\Support\Facades\Log;

class LogChannel implements ChannelInterface
{
    public function send(string $to, string $message): bool
    {
        Log::info("[LOG_CHANNEL] To: {$to}\n{$message}");
        return true;
    }
}
