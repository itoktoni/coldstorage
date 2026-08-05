<?php

/**
 * Scenario 1: 1 SO with 1 Product
 *
 * Flow: Stock 10kg → SO 5kg → Prepare → Keluar → Scan staging → Verify stock
 */

use App\Livewire\KeluarRealisasiScan;
use App\Livewire\SoPrepareScan;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Models\User;
use App\Wms\SoStatusEnum;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutMiddleware();

    $this->actingAs(User::create([
        'name' => 'Test User',
        'email' => 'test-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'verified_at' => now(),
    ]));

    $uniqid = uniqid();

    // Create master data
    $this->gudang = Gudang::create([
        'gudang_code' => 'GD-'.$uniqid,
        'gudang_nama' => 'Gudang '.$uniqid,
    ]);

    $this->lokasiRack = Lokasi::create([
        'lokasi_code' => 'RACK-'.$uniqid,
        'lokasi_nama' => 'Rack '.$uniqid,
        'lokasi_code_gudang' => $this->gudang->gudang_code,
        'lokasi_category' => 'meat',
        'lokasi_max_qty' => 1000,
    ]);

    $this->lokasiStaging = Lokasi::create([
        'lokasi_code' => 'STG-'.$uniqid,
        'lokasi_nama' => 'Staging '.$uniqid,
        'lokasi_code_gudang' => $this->gudang->gudang_code,
        'lokasi_category' => 'staging',
        'lokasi_max_qty' => 500,
    ]);

    $this->product = Product::create([
        'product_nama' => 'Daging Sapi',
        'product_harga' => 50000,
        'product_category' => 'meat',
    ]);

    $this->customer = Customer::create([
        'customer_nama' => 'Customer Test',
        'customer_telepon' => '081234567890',
    ]);

    // Create initial stock: 10kg IN
    $this->stock = Stock::create([
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasiRack->lokasi_code,
        'stock_qty' => 10,
        'stock_type' => Stock::TYPE_IN,
        'stock_expired_date' => now()->addDays(30),
    ]);

    config(['scan.prefix.pallet' => 'P', 'scan.prefix.location' => 'L', 'scan.prefix.barcode' => 'B']);
});

it('Scenario 1: 1 SO 1 Product - full workflow', function () {
    // ============================================
    // STEP 1: Create SO with 5kg of product
    // ============================================
    $response = $this->postJson(route('wms-so.postCreate'), [
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'details' => [
            ['so_detail_id_product' => $this->product->product_id, 'so_detail_qty' => 5],
        ],
    ]);

    $response->assertOk();
    $so = So::latest()->first();

    // Verify SO created with Pending status
    expect($so->so_status)->toBe(SoStatusEnum::PENDING);

    // Verify RESERVE stock created
    $reserve = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $so->so_code)
        ->where('stock_id_product', $this->product->product_id)
        ->first();

    expect($reserve)->not->toBeNull()
        ->and($reserve->stock_qty)->toBe(5.0);

    // ============================================
    // STEP 2: Prepare SO → creates Keluar
    // ============================================
    $soDetail = $so->details()->first();

    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$so->so_id],
        'details' => [
            [
                'so_detail_id' => $soDetail->so_detail_id,
                'product_id' => $this->product->product_id,
                'qty' => 5,
            ],
        ],
    ]);

    $response->assertRedirect(route('wms-so.getTable'));

    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::PREPARE);

    $keluar = Keluar::where('out_reff', 'Prepare SO')->latest()->first();
    expect($keluar)->not->toBeNull()
        ->and($keluar->out_status)->toBe('Pending');

    $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->first();
    expect($keluarDetail)->not->toBeNull()
        ->and($keluarDetail->out_detail_qty)->toBe(5.0);

    // ============================================
    // STEP 3: Assign stock to keluar detail
    // ============================================
    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $keluarDetail->out_detail_id,
                'stock_id' => $this->stock->stock_id,
                'qty' => 5,
                'so_detail_id' => $soDetail->so_detail_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    $assignment = StockAssignment::where('stock_assignment_id_keluar', $keluar->out_code)->first();
    expect($assignment)->not->toBeNull()
        ->and($assignment->stock_assignment_qty)->toBe(5.0);

    // ============================================
    // STEP 4: Scan via KeluarRealisasiScan (pick from staging)
    // ============================================
    // First, simulate forklift moving stock to staging
    $stagingStock = Stock::create([
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 5,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_reff' => $keluar->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    // Create StockAssignment for the staging stock (required by KeluarRealisasiScan)
    StockAssignment::create([
        'stock_assignment_id_keluar' => $keluar->out_code,
        'stock_assignment_id_stock' => $stagingStock->stock_id,
        'stock_assignment_id_keluar_detail' => $keluarDetail->out_detail_id,
        'stock_assignment_id_so_detail' => $soDetail->so_detail_id,
        'stock_assignment_qty' => 5,
        'stock_assignment_status' => 'Pending',
    ]);

    // Scan the staging stock
    $livewire = Livewire::test(KeluarRealisasiScan::class, ['detailId' => $keluarDetail->out_detail_id])
        ->call('scan', $stagingStock->stock_code);

    // Check for errors
    $errorMsg = $livewire->get('errorMsg');
    if (!empty($errorMsg)) {
        fwrite(STDERR, 'SCAN ERROR: ' . $errorMsg . PHP_EOL);
    }

    $livewire->assertSet('successMsg', 'Scan berhasil. Stock dialokasikan ke keluar.');

    // Verify stock states
    $this->stock->refresh();
    expect($this->stock->stock_qty)->toBe(10.0); // IN stock unchanged (picked from staging)

    $stagingStock->refresh();
    expect($stagingStock->stock_qty)->toBe(0.0); // Staging stock consumed

    // Verify RESERVE consumed
    $reserve->refresh();
    expect($reserve->stock_qty)->toBe(0.0);

    // Verify Keluar status → Done
    $keluar->refresh();
    expect($keluar->out_status)->toBe(Keluar::STATUS_DONE);

    // Verify SO status → Confirmed
    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::CONFIRMED);

    // ============================================
    // STEP 5: Verify final stock state
    // ============================================
    $totalIn = Stock::where('stock_type', Stock::TYPE_IN)
        ->where('stock_id_product', $this->product->product_id)
        ->sum('stock_qty');

    $totalReserve = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_id_product', $this->product->product_id)
        ->sum('stock_qty');

    $totalStaging = Stock::where('stock_type', Stock::TYPE_STAGING)
        ->where('stock_id_product', $this->product->product_id)
        ->sum('stock_qty');

    // IN: 10kg (unchanged), RESERVE: 0kg (consumed), STAGING: 0kg (consumed by scan)
    expect($totalIn)->toBe(10.0)
        ->and($totalReserve)->toBe(0.0)
        ->and($totalStaging)->toBe(0.0);
});
