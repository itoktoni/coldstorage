<?php

use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Cms\ContentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImageScannerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\Wms\BarcodeController;
use App\Http\Controllers\Wms\ForkliftController;
use App\Http\Controllers\Wms\ForkliftTaskController;
use App\Http\Controllers\Wms\KeluarController;
use App\Http\Controllers\Wms\LokasiController;
use App\Http\Controllers\Wms\MasukDetailController;
use App\Http\Controllers\Wms\PoController;
use App\Http\Controllers\Wms\PoDetailController;
use App\Http\Controllers\Wms\ProductController;
use App\Http\Controllers\Wms\SoController;
use App\Http\Controllers\Wms\SplitController;
use App\Http\Controllers\Wms\StagingRecapController;
use App\Http\Controllers\Wms\StockCardController;
use App\Http\Controllers\Wms\StockFlowController;
use App\Http\Controllers\Wms\StockSalesController;
use App\Models\Notification;
use App\Services\CentrifugoService;
use Buki\AutoRoute\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/', [PublicController::class, 'index'])->name('home');
Route::get('/api/content/{slug?}', [PublicController::class, 'api'])->name('api.content');

Route::middleware('auth')->post('/centrifugo/token', function (Request $request) {
    if (! config('langkahkecil.notification_enable')) {
        return response()->json(['token' => 'disabled']);
    }

    $centrifugo = app(CentrifugoService::class);
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

    Route::get('image-scanner', ImageScannerController::class)->name('image-scanner');
    Route::get('image-scanner/photo/{path}', ImageScannerController::class)->name('image-scanner.photo')->where('path', '.*');

    Route::auto('/user', 'UsersController', ['name' => 'user']);

    // WMS Master Data
    Route::auto('/wms/gudang', 'Wms\GudangController', ['name' => 'wms-gudang']);
    Route::auto('/wms/supplier', 'SupplierController', ['name' => 'wms-supplier']);
    Route::auto('/wms/customer', 'CustomerController', ['name' => 'wms-customer']);
    Route::auto('/wms/lokasi', 'Wms\LokasiController', ['name' => 'wms-lokasi']);
    Route::get('/wms/lokasi/{code}/print-qr', [LokasiController::class, 'printQrPdf'])->name('wms-lokasi.printQr');
    Route::auto('/wms/product', 'Wms\ProductController', ['name' => 'wms-product']);
    Route::get('/wms/product/{id}/qrcode', [ProductController::class, 'getQrcode'])->name('wms-product.getQrcode');
    Route::post('/wms/product/{id}/qrcode', [ProductController::class, 'postQrcode'])->name('wms-product.postQrcode');

    // WMS Inventory
    Route::auto('/wms/stock', 'Wms\StockController', ['name' => 'wms-stock']);
    Route::get('/wms/stock-flow', [StockFlowController::class, 'index'])->name('wms-stock-flow.index');
    Route::get('/wms/stock-sales', [StockSalesController::class, 'index'])->name('wms-stock-sales.index');
    Route::get('/wms/stock-card', [StockCardController::class, 'index'])->name('wms-stock-card.index');

    // WMS Procurement
    Route::auto('/wms/po', 'Wms\PoController', ['name' => 'wms-po']);
    Route::get('/wms/po/{id}/cetak', [PoController::class, 'cetak'])->name('wms-po.cetak');
    Route::auto('/wms/po-detail', 'Wms\PoDetailController', ['name' => 'wms-po-detail']);
    Route::get('/wms/po-detail/{id}/convert-to-masuk', [PoDetailController::class, 'getConvertToMasuk'])->name('wms-po-detail-convert');
    Route::post('/wms/po-detail/{id}/convert-to-masuk', [PoDetailController::class, 'postConvertToMasuk'])->name('wms-po-detail.convertToMasuk');
    Route::post('/wms/po-detail/{id}/convert-single', [PoDetailController::class, 'postConvertSingleRow'])->name('wms-po-detail.convertSingle');

    // WMS Sales
    Route::auto('/wms/so', 'Wms\SoController', ['name' => 'wms-so', 'except' => ['getPrepare', 'postPrepare', 'getPrepareList', 'getPrepareSo', 'postPrepareSo', 'cetak', 'ship', 'storeShip', 'cetakInvoice', 'cetakPerformance', 'cetakDelivery']]);
    Route::get('/wms/so/{id}/cetak', [SoController::class, 'cetak'])->name('wms-so.cetak');
    Route::get('/wms/so/{id}/ship', [SoController::class, 'ship'])->name('wms-so.ship');
    Route::post('/wms/so/{id}/ship', [SoController::class, 'storeShip'])->name('wms-so.storeShip');
    Route::get('/wms/so/{id}/cetak-invoice', [SoController::class, 'cetakInvoice'])->name('wms-so.cetakInvoice');
    Route::get('/wms/so/{id}/cetak-performance', [SoController::class, 'cetakPerformance'])->name('wms-so.cetakPerformance');
    Route::get('/wms/so/{id}/cetak-delivery', [SoController::class, 'cetakDelivery'])->name('wms-so.cetakDelivery');

    // WMS SO Prepare - prefix terpisah (bukan wms-so.*) biar menu aktif tidak bentrok
    Route::get('/wms/so-prepare', [SoController::class, 'getPrepareList'])->name('wms-so-prepare.index');
    Route::get('/wms/so-prepare/create', [SoController::class, 'getPrepare'])->name('wms-so-prepare.create');
    Route::post('/wms/so-prepare', [SoController::class, 'postPrepare'])->name('wms-so-prepare.store');
    Route::get('/wms/so-prepare/{soId}', [SoController::class, 'getPrepareSo'])->name('wms-so-prepare.show');
    Route::post('/wms/so-prepare/{soId}', [SoController::class, 'postPrepareSo'])->name('wms-so-prepare.update');
    Route::get('/wms/so-prepare/{soId}/assign', [SoController::class, 'getAssign'])->name('wms-so-prepare.assign');
    Route::post('/wms/so-prepare/{soId}/assign', [SoController::class, 'postAssign'])->name('wms-so-prepare.assignStore');

    // WMS Staging Recap → Putaway
    Route::get('/wms/staging-recap', [StagingRecapController::class, 'index'])->name('wms-staging-recap.index');
    Route::get('/wms/staging-recap/{lokasiCode}', [StagingRecapController::class, 'show'])->name('wms-staging-recap.show');

    // WMS Keluar Prepare - jalur global untuk prepare dari keluar table
    Route::get('/wms/keluar-prepare/{outCode}', [KeluarController::class, 'getPrepare'])->name('wms-keluar-prepare.show');
    Route::post('/wms/keluar-prepare/{outCode}', [KeluarController::class, 'postPrepare'])->name('wms-keluar-prepare.update');
    Route::get('/wms/keluar-pick-list/{outCode}', [KeluarController::class, 'pickList'])->name('wms-keluar.pickList');

    // WMS Inbound
    Route::auto('/wms/masuk-detail', 'Wms\MasukDetailController', ['name' => 'wms-masuk-detail']);
    Route::get('/wms/masuk-detail/{id}/realisasikan', [MasukDetailController::class, 'getRealisasikan'])->name('wms-masuk-detail.realisasikan');
    Route::post('/wms/masuk-detail/{id}/realisasikan', [MasukDetailController::class, 'postRealisasikan'])->name('wms-masuk-detail.postRealisasikan');
    Route::auto('/wms/masuk-realisasi', 'Wms\MasukRealisasiController', ['name' => 'wms-masuk-realisasi']);

    // WMS Forklift
    Route::get('/wms/forklift', [ForkliftTaskController::class, 'index'])->name('wms-forklift.index');
    Route::post('/wms/forklift', [ForkliftController::class, 'store'])->name('wms-forklift.store');
    Route::get('/wms/forklift/{groupCode}/print-qr', [ForkliftController::class, 'printGroupQr'])->name('wms-forklift.printQr');
    Route::post('/wms/forklift/relokasi', [ForkliftController::class, 'relokasi'])->name('wms-forklift.relokasi');
    Route::get('/wms/forklift-pick/{outCode}', [ForkliftController::class, 'pick'])->name('wms-forklift-pick.show');
    Route::post('/wms/forklift-pick/{outCode}', [ForkliftController::class, 'pickStore'])->name('wms-forklift-pick.update');
    Route::get('/wms/forklift-pick/{outCode}/scan', [ForkliftController::class, 'pickScan'])->name('wms-forklift-pick.scan');
    Route::post('/wms/forklift-pick/{outCode}/scan', [ForkliftController::class, 'pickScanProcess'])->name('wms-forklift-pick.scanProcess');

    // WMS Forklift Task
    Route::get('/wms/forklift-task', [ForkliftTaskController::class, 'index'])->name('wms-forklift-task.index');
    Route::post('/wms/forklift-task/scan', [ForkliftTaskController::class, 'scan'])->name('wms-forklift-task.scan');

    // WMS Outbound
    Route::auto('/wms/keluar', 'Wms\KeluarController', ['name' => 'wms-keluar', 'except' => ['getPrepare', 'postPrepare']]);
    Route::auto('/wms/keluar-detail', 'Wms\KeluarDetailController', ['name' => 'wms-keluar-detail']);
    Route::get('/wms/keluar-realisasi-scan/{detailId}', [KeluarController::class, 'realisasiScan'])->name('wms-keluar-realisasi-scan.show');
    Route::auto('/wms/keluar-realisasi', 'Wms\KeluarRealisasiController', ['name' => 'wms-keluar-realisasi']);

    // WMS Split
    Route::auto('/wms/split', 'Wms\SplitController', ['name' => 'wms-split']);
    Route::get('/wms/split/produce', [SplitController::class, 'getProduce'])->name('wms-split.produce');

    // WMS Barcode
    Route::get('/wms/barcode/generate', [BarcodeController::class, 'generate'])->name('wms-barcode.generate');
    Route::post('/wms/barcode/generate', [BarcodeController::class, 'postGenerate'])->name('wms-barcode.postGenerate');
    Route::post('/wms/barcode/pdf', [BarcodeController::class, 'getPdf'])->name('wms-barcode.pdf');

    // CMS Routes
    Route::auto('/cms/type', 'Cms\TypeController', ['name' => 'cms-type']);
    Route::auto('/cms/field', 'Cms\FieldController', ['name' => 'field']);
    Route::auto('/cms/section', 'Cms\SectionController', ['name' => 'section']);
    Route::auto('/cms/content', 'Cms\ContentController', ['name' => 'content']);
    Route::auto('/cms/category', 'Cms\CategoryController', ['name' => 'category']);
    Route::auto('/cms/tag', 'Cms\TagController', ['name' => 'tag']);
    Route::auto('/cms/menu', 'Cms\MenuController', ['name' => 'menu']);

    // Section HTML API (AJAX section loading)
    Route::get('/cms/content-entry/field-group-html/{id}', [ContentController::class, 'getSectionHtml'])->name('cms.section.html');

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
Route::get('/services', [PublicController::class, 'services'])->name('services');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::get('/blog', [PublicController::class, 'blog'])->name('blog');
Route::get('/blog/category/{slug}', [PublicController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [PublicController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [PublicController::class, 'post'])->name('blog.post');
Route::get('/search', [PublicController::class, 'search'])->name('search');

// Documentation routes (photo gallery with categories and tags)
Route::get('/documentation', [PublicController::class, 'documentation'])->name('documentation');
Route::get('/documentation/category/{slug}', [PublicController::class, 'documentationCategory'])->name('documentation.category');
Route::get('/documentation/tag/{slug}', [PublicController::class, 'documentationTag'])->name('documentation.tag');
Route::get('/documentation/{slug}', [PublicController::class, 'documentationShow'])->name('documentation.show');

Route::get('/{slug}', [PublicController::class, 'page'])->name('page');
require __DIR__.'/settings.php';
