<?php

namespace App\Telegram\ContentType;

interface ContentTypeInterface
{
    public function description(): string;

    public function emoji(): string;

    public function systemPrompt(): string;

    public function userPrompt(string $topic): string;

    public function batchFormatSpec(): string;

    public function formatOutput(array $result): string;
}
