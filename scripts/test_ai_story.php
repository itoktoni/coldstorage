<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\StoryGeneratorService;

$service = new StoryGeneratorService();
$result = $service->generateWithAI('kejujuran', 'Siti', 4);

echo "Source: " . ($result['source'] ?? 'unknown') . "\n";
echo "Title: " . $result['title'] . "\n";
echo "Moral: " . $result['moral'] . "\n";
echo "Pages count: " . count($result['pages']) . "\n";
foreach ($result['pages'] as $page) {
    echo "  Page " . $page['num'] . ": " . $page['text'] . "\n";
}
