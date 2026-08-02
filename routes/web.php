<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\DashboardController;
use App\Models\Notification;
use Buki\AutoRoute\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', [\App\Http\Controllers\PublicController::class, 'index'])->name('home');
Route::get('/api/content/{slug?}', [\App\Http\Controllers\PublicController::class, 'api'])->name('api.content');

Route::middleware('auth')->post('/centrifugo/token', function (Request $request) {
    if (!config('langkahkecil.notification_enable')) {
        return response()->json(['token' => 'disabled']);
    }

    $centrifugo = app(\App\Services\CentrifugoService::class);
    $user = Auth::user();

    if ($request->input('channel')) {
        return response()->json([
            'token' => $centrifugo->generateSubscriptionToken((string) $user->id, $request->input('channel')),
        ]);
    }

    return response()->json([
        'token' => $centrifugo->generateConnectionToken((string) $user->id),
    ]);
});
Route::middleware(['auth', 'verified', 'access'])->group(function () {

    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('image-scanner', \App\Http\Controllers\ImageScannerController::class)->name('image-scanner');
    Route::get('image-scanner/photo/{path}', \App\Http\Controllers\ImageScannerController::class)->name('image-scanner.photo')->where('path', '.*');

    Route::auto('/user', 'UsersController', ['name' => 'user']);

    // WMS Master Data
    Route::auto('/wms/gudang', 'Wms\GudangController', ['name' => 'wms-gudang']);
    Route::auto('/wms/supplier', 'SupplierController', ['name' => 'wms-supplier']);
    Route::auto('/wms/customer', 'CustomerController', ['name' => 'wms-customer']);
    Route::auto('/wms/lokasi', 'Wms\LokasiController', ['name' => 'wms-lokasi']);
    Route::get('/wms/lokasi/{code}/print-qr', [\App\Http\Controllers\Wms\LokasiController::class, 'printQrPdf'])->name('wms-lokasi.printQr');
    Route::auto('/wms/product', 'Wms\ProductController', ['name' => 'wms-product']);
    Route::get('/wms/product/{id}/qrcode', [\App\Http\Controllers\Wms\ProductController::class, 'getQrcode'])->name('wms-product.getQrcode');
    Route::post('/wms/product/{id}/qrcode', [\App\Http\Controllers\Wms\ProductController::class, 'postQrcode'])->name('wms-product.postQrcode');

    // WMS Inventory
    Route::auto('/wms/stock', 'Wms\StockController', ['name' => 'wms-stock']);

    // WMS Procurement
    Route::auto('/wms/po', 'Wms\PoController', ['name' => 'wms-po']);
    Route::get('/wms/po/{id}/cetak', [\App\Http\Controllers\Wms\PoController::class, 'cetak'])->name('wms-po.cetak');
    Route::auto('/wms/po-detail', 'Wms\PoDetailController', ['name' => 'wms-po-detail']);
    Route::get('/wms/po-detail/{id}/convert-to-masuk', [\App\Http\Controllers\Wms\PoDetailController::class, 'getConvertToMasuk'])->name('wms-po-detail-convert');
    Route::post('/wms/po-detail/{id}/convert-to-masuk', [\App\Http\Controllers\Wms\PoDetailController::class, 'postConvertToMasuk'])->name('wms-po-detail.convertToMasuk');
    Route::post('/wms/po-detail/{id}/convert-single', [\App\Http\Controllers\Wms\PoDetailController::class, 'postConvertSingleRow'])->name('wms-po-detail.convertSingle');

    // WMS Sales
    Route::auto('/wms/so', 'Wms\SoController', ['name' => 'wms-so']);
    Route::get('/wms/so/{id}/cetak', [\App\Http\Controllers\Wms\SoController::class, 'cetak'])->name('wms-so.cetak');

    // WMS Inbound
    Route::auto('/wms/masuk-detail', 'Wms\MasukDetailController', ['name' => 'wms-masuk-detail']);
    Route::get('/wms/masuk-detail/{id}/realisasikan', [\App\Http\Controllers\Wms\MasukDetailController::class, 'getRealisasikan'])->name('wms-masuk-detail.realisasikan');
    Route::post('/wms/masuk-detail/{id}/realisasikan', [\App\Http\Controllers\Wms\MasukDetailController::class, 'postRealisasikan'])->name('wms-masuk-detail.postRealisasikan');
    Route::auto('/wms/masuk-realisasi', 'Wms\MasukRealisasiController', ['name' => 'wms-masuk-realisasi']);

    // WMS Forklift
    Route::get('/wms/forklift', [\App\Http\Controllers\Wms\ForkliftController::class, 'index'])->name('wms-forklift.index');
    Route::post('/wms/forklift', [\App\Http\Controllers\Wms\ForkliftController::class, 'store'])->name('wms-forklift.store');
    Route::get('/wms/forklift/{groupCode}/print-qr', [\App\Http\Controllers\Wms\ForkliftController::class, 'printGroupQr'])->name('wms-forklift.printQr');
    Route::post('/wms/forklift/relokasi', [\App\Http\Controllers\Wms\ForkliftController::class, 'relokasi'])->name('wms-forklift.relokasi');

    // WMS Outbound
    Route::auto('/wms/keluar', 'Wms\KeluarController', ['name' => 'wms-keluar']);
    Route::auto('/wms/keluar-detail', 'Wms\KeluarDetailController', ['name' => 'wms-keluar-detail']);
    Route::auto('/wms/keluar-realisasi', 'Wms\KeluarRealisasiController', ['name' => 'wms-keluar-realisasi']);

    // WMS Split
    Route::auto('/wms/split', 'Wms\SplitController', ['name' => 'wms-split']);

    // WMS Barcode
    Route::get('/wms/barcode/generate', [\App\Http\Controllers\Wms\BarcodeController::class, 'generate'])->name('wms-barcode.generate');
    Route::post('/wms/barcode/generate', [\App\Http\Controllers\Wms\BarcodeController::class, 'postGenerate'])->name('wms-barcode.postGenerate');
    Route::post('/wms/barcode/pdf', [\App\Http\Controllers\Wms\BarcodeController::class, 'getPdf'])->name('wms-barcode.pdf');

    // CMS Routes
    Route::auto('/cms/type', 'Cms\TypeController', ['name' => 'cms-type']);
    Route::auto('/cms/field', 'Cms\FieldController', ['name' => 'field']);
    Route::auto('/cms/section', 'Cms\SectionController', ['name' => 'section']);
    Route::auto('/cms/content', 'Cms\ContentController', ['name' => 'content']);
    Route::auto('/cms/category', 'Cms\CategoryController', ['name' => 'category']);
    Route::auto('/cms/tag', 'Cms\TagController', ['name' => 'tag']);
    Route::auto('/cms/menu', 'Cms\MenuController', ['name' => 'menu']);

    // Section HTML API (AJAX section loading)
    Route::get('/cms/content-entry/field-group-html/{id}', [\App\Http\Controllers\Cms\ContentController::class, 'getSectionHtml'])->name('cms.section.html');

    // Media API Routes
    Route::prefix('api/media')->group(function () {
        Route::get('/', [MediaController::class, 'index']);
        Route::post('/upload', [MediaController::class, 'upload']);
        Route::delete('/{media}', [MediaController::class, 'destroy']);
    });

    Route::prefix('notifications-web')->group(function () {
        Route::get('/', function (Request $request) {
            $notifications = Notification::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit($request->input('limit', 50))
                ->get();

            $unreadCount = Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->count();

            return response()->json([
                'notifications' => $notifications->map(fn ($n) => [
                    'id' => $n->id,
                    'icon' => $n->icon,
                    'iconColor' => $n->icon_color,
                    'title' => $n->title,
                    'body' => $n->body,
                    'url' => $n->url,
                    'type' => $n->type,
                    'read' => $n->read,
                    'time' => $n->created_at?->diffForHumans() ?? '',
                    'created_at' => $n->created_at->toIso8601String(),
                ]),
                'unread_count' => $unreadCount,
            ]);
        });

        Route::put('/{id}/read', function (int $id) {
            $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
            $notification->update(['read' => true]);

            return response()->json(['message' => 'Marked as read']);
        });

        Route::put('/read-all', function () {
            Notification::where('user_id', Auth::id())
                ->where('read', false)
                ->update(['read' => true]);

            return response()->json(['message' => 'All marked as read']);
        });
    });
});

// Frontend public routes (must be before catch-all /{slug})
Route::get('/services', [\App\Http\Controllers\PublicController::class, 'services'])->name('services');
Route::get('/contact', [\App\Http\Controllers\PublicController::class, 'contact'])->name('contact');
Route::get('/blog', [\App\Http\Controllers\PublicController::class, 'blog'])->name('blog');
Route::get('/blog/category/{slug}', [\App\Http\Controllers\PublicController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [\App\Http\Controllers\PublicController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [\App\Http\Controllers\PublicController::class, 'post'])->name('blog.post');
Route::get('/search', [\App\Http\Controllers\PublicController::class, 'search'])->name('search');

// Documentation routes (photo gallery with categories and tags)
Route::get('/documentation', [\App\Http\Controllers\PublicController::class, 'documentation'])->name('documentation');
Route::get('/documentation/category/{slug}', [\App\Http\Controllers\PublicController::class, 'documentationCategory'])->name('documentation.category');
Route::get('/documentation/tag/{slug}', [\App\Http\Controllers\PublicController::class, 'documentationTag'])->name('documentation.tag');
Route::get('/documentation/{slug}', [\App\Http\Controllers\PublicController::class, 'documentationShow'])->name('documentation.show');

Route::get('/{slug}', [\App\Http\Controllers\PublicController::class, 'page'])->name('page');
require __DIR__.'/settings.php';
