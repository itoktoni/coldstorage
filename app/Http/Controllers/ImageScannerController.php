<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class ImageScannerController extends Controller
{
    public function __invoke(): View
    {
        return view('pages.image-scanner');
    }

    public function photo(string $path): Response
    {
        if (!Storage::disk('local')->exists($path) && !file_exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('local')->path($path);

        if (!file_exists($fullPath)) {
            $fullPath = $path;
        }

        if (!file_exists($fullPath)) {
            abort(404);
        }

        $mime = mime_content_type($fullPath);
        $content = file_get_contents($fullPath);

        return new Response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache',
        ]);
    }
}
