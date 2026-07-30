<?php

namespace App\Contracts;

interface ChannelInterface
{
    public function send(string $to, string $message): bool;
}
