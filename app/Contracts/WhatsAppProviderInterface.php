<?php

namespace App\Contracts;

interface WhatsAppProviderInterface
{
    public function send(string $to, string $message): bool;
}
