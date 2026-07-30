<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

$baseUrl = rtrim((string) 'https://ai.sumopod.com/v1', '/');
$apiKey = (string) 'sk-kVhtiRFNXLlFG1bTgNvnqw';

$client = Http::withToken($apiKey)
    ->asJson()
    ->baseUrl($baseUrl)
    ->timeout(60)
    ->retry(2, 500)
    ->acceptJson();

$systemPrompt = "You are a children's story writer for ages 3-8.\n";
$systemPrompt .= "Write a story in Indonesian with EXACTLY 4 pages.\n";
$systemPrompt .= "Return ONLY JSON with this structure:\n";
$systemPrompt .= "{\"title\":\"...\",\"moral\":\"...\",\"pages\":[{\"text\":\"...\"}]}\n";
$systemPrompt .= "- Each page must be 1-2 sentences, simple and cheerful\n";
$systemPrompt .= "- Include a moral/lesson at the end\n";
$systemPrompt .= "- Theme: kejujuran\n";
$systemPrompt .= "- Child name: Siti\n";
$systemPrompt .= "- Number of pages: 4\n";

$response = $client->post('/chat/completions', [
    'model' => 'MiniMax-M2.7-highspeed',
    'messages' => [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => 'Buatkan cerita tentang tema: kejujuran untuk anak bernama Siti'],
    ],
    'temperature' => 0.7,
    'max_tokens' => 2000,
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
