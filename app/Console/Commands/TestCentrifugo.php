<?php

namespace App\Console\Commands;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use App\Services\CentrifugoService;
use Illuminate\Console\Command;

class TestCentrifugo extends Command
{
    protected $signature = 'notify:test
        {--user=1 : User ID to send test notification to}
        {--skip-api : Skip HTTP API connectivity test}';

    protected $description = 'Test Centrifugo notification: verify API connection and send a real-time notification';

    public function handle(CentrifugoService $centrifugo): int
    {
        $this->info('=== Centrifugo Notification Test ===');
        $this->newLine();

        // Step 1: Check config
        $this->line('1. Checking config...');
        $url = config('centrifugo.url');
        $apiKey = config('centrifugo.api_key');
        $hmacSecret = config('centrifugo.hmac_secret');

        if (! $url || ! $apiKey || ! $hmacSecret) {
            $this->error('   Missing CENTRIFUGO_URL, CENTRIFUGO_API_KEY, or CENTRIFUGO_HMAC_SECRET in .env');
            return self::FAILURE;
        }
        $this->info("   URL: {$url}");
        $this->info("   API Key: {$apiKey}");
        $this->info("   HMAC Secret: {$hmacSecret}");
        $this->newLine();

        // Step 2: Test HTTP API connectivity
        if (! $this->option('skip-api')) {
            $this->line('2. Testing HTTP API connection...');
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'Authorization' => 'apikey ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(5)->post($url . '/api', [
                    'method' => 'info',
                    'params' => (object) [],
                ]);

                if ($response->successful()) {
                    $this->info('   Connection OK! Centrifugo is running.');
                    $info = $response->json('result');
                    if ($info) {
                        $this->line('   Version: ' . ($info['version'] ?? 'unknown'));
                    }
                } else {
                    $this->error('   HTTP ' . $response->status() . ': ' . $response->body());
                    return self::FAILURE;
                }
            } catch (\Throwable $e) {
                $this->error('   Connection failed: ' . $e->getMessage());
                $this->line('   Make sure Centrifugo is running: centrifugo -c D:\kepompong\config.json');
                return self::FAILURE;
            }
            $this->newLine();
        }

        // Step 3: Test JWT token generation
        $this->line('3. Testing JWT token generation...');
        $userId = $this->option('user');
        $channel = "notifications#{$userId}";
        try {
            $connToken = $centrifugo->generateConnectionToken($userId);
            $subToken = $centrifugo->generateSubscriptionToken($userId, $channel);
            $this->info('   Connection token: ' . substr($connToken, 0, 40) . '...');
            $this->info('   Subscription token: ' . substr($subToken, 0, 40) . '...');
        } catch (\Throwable $e) {
            $this->error('   Token generation failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // Step 4: Publish test notification via Centrifugo HTTP API
        $this->line("4. Publishing to channel \"{$channel}\"...");
        try {
            $testData = [
                'id' => 'test-' . time(),
                'icon' => '🧪',
                'iconColor' => '#176c33',
                'title' => 'Test Centrifugo',
                'body' => 'Notifikasi real-time berhasil! (' . now()->format('H:i:s') . ')',
                'url' => null,
                'type' => 'test',
                'read' => false,
                'meta' => null,
                'time' => 'Baru saja',
                'created_at' => now()->toIso8601String(),
            ];

            $centrifugo->publish($channel, $testData);
            $this->info('   Published successfully!');
        } catch (\Throwable $e) {
            $this->error('   Publish failed: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->newLine();

        // Step 5: Send real notification via event (saves to DB + publishes)
        $this->line('5. Creating DB notification + dispatching event...');
        $user = User::find($userId);
        if (! $user) {
            $this->error("   User #{$userId} not found.");
            return self::FAILURE;
        }

        $notification = Notification::create([
            'user_id' => (int) $userId,
            'icon' => '🎉',
            'icon_color' => '#176c33',
            'title' => 'Notifikasi Real-time!',
            'body' => 'Ini adalah test notifikasi dari Centrifugo pada ' . now()->format('d M Y H:i:s'),
            'type' => 'test',
        ]);

        NotificationSent::dispatch((int) $userId, $notification);
        $this->info('   Notification #' . $notification->id . ' created and dispatched!');
        $this->newLine();

        // Summary
        $this->info('=== Test Complete ===');
        $this->line('');
        $this->line('Check the browser now. You should see:');
        $this->line('  1. A toast notification in the top-right corner');
        $this->line('  2. The notification bell count increased');
        $this->line('  3. The notification in the dropdown list');
        $this->line('');
        $this->line('If nothing appears, open browser DevTools > Console and check for errors.');
        $this->line('Also check the Centrifugo terminal for request logs.');

        return self::SUCCESS;
    }
}
