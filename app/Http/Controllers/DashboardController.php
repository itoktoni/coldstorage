<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Content;
use App\Models\Section;
use App\Models\Tag;
use App\Models\Type;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'total_content' => Content::count(),
            'total_sections' => Section::count(),
            'total_categories' => Category::count(),
            'total_tags' => Tag::count(),
        ];

        $cmsTypes = Type::withCount('contents')->latest()->get();

        $recentContents = Content::latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($content) => [
                'icon' => match ($content->status) {
                    'published' => 'check_circle',
                    default => 'edit_note',
                },
                'iconBg' => match ($content->status) {
                    'published' => 'bg-green-50',
                    default => 'bg-primary/5',
                },
                'iconColor' => match ($content->status) {
                    'published' => 'text-green-600',
                    default => 'text-primary',
                },
                'title' => $content->title,
                'subtitle' => $content->type?->name ?? 'Draft',
                'status' => strtoupper($content->status),
                'statusClass' => match ($content->status) {
                    'published' => 'bg-green-50 text-green-700',
                    default => 'bg-surface-container-high text-on-surface-variant',
                },
                'time' => $content->updated_at?->diffForHumans() ?? '',
            ]);

        return view('dashboard', compact('stats', 'cmsTypes', 'recentContents'));
    }
}
