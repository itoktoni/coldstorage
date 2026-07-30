<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Activity;

$activity = Activity::orderByDesc('id')->first();

if (!$activity) {
    echo "No activities found\n";
    exit(1);
}

echo "ID: " . $activity->id . "\n";
echo "Title: " . $activity->title . "\n";
echo "Type: " . $activity->type . "\n";
echo "Slug: " . $activity->slug . "\n";
echo "Status: " . $activity->status . "\n";
echo "Moral: " . $activity->moral . "\n";
echo "Desc: " . substr($activity->desc, 0, 80) . "...\n";
echo "Pages count: " . count($activity->data['pages'] ?? []) . "\n";
echo "Image: " . ($activity->image ?: 'none') . "\n";
echo "First image: " . ($activity->data['pages'][0]['image'] ?? 'none') . "\n";
echo "Last image: " . ($activity->data['pages'][count($activity->data['pages'])-1]['image'] ?? 'none') . "\n";
