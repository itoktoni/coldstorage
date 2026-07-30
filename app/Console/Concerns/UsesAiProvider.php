<?php

namespace App\Console\Concerns;

use App\Services\AiService;

trait UsesAiProvider
{
    protected function resolveAi(?string $provider = null, ?string $model = null): array
    {
        $ai = app(AiService::class);

        $provider = $provider ?: $this->option('provider');
        $model = $model ?: $this->option('model');

        if (!$provider) {
            $commandKey = $this->guessCommandKey();
            $provider = config("ai.commands.{$commandKey}") ?: config('ai.default_provider');
        }

        if ($provider) {
            try {
                $ai->getProvider($provider);
            } catch (\InvalidArgumentException $e) {
                $this->error($e->getMessage());
                $this->line("Run <comment>php artisan ai:provider</comment> to list available providers.");
                return [null, null, null];
            }
        }

        $resolvedProvider = $provider ?: config('ai.default_provider');
        $resolvedModel = $ai->getModel($provider, $model);

        $this->line("  AI Provider : {$resolvedProvider}");
        $this->line("  AI Model    : {$resolvedModel}");

        return [$ai, $resolvedProvider, $resolvedModel];
    }

    private function guessCommandKey(): string
    {
        $name = $this->getName() ?? '';

        return match (true) {
            str_contains($name, 'idea')     => 'idea',
            str_contains($name, 'activity') => 'activity',
            str_contains($name, 'image')    => 'image',
            str_contains($name, 'telegram') => 'marketing',
            default                         => 'activity',
        };
    }
}
