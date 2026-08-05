<?php

/**
 * Scenario 4: 2 SOs with Different Products
 *
 * Flow: Stock 10kg product A, 8kg product B
 *   - SO A: 3kg product A (via Sales Order direct)
 *   - SO B: 5kg product B (via Keluar)
 *
 * This tests independent stock tracking for different products
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
        'name' => 'Test User 4',
        'email' => 'test-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'verified_at' => now(),
    ]));

    $uniqid = uniqid();

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

    $this->productA = Product::create([
        'product_nama' => 'Daging Sapi',
        'product_harga' => 50000,
        'product_category' => 'meat',
    ]);

    $this->productB = Product::create([
        'product_nama' => 'Daging Ayam',
        'product_harga' => 30000,
        'product_category' => 'meat',
    ]);

    $this->customer = Customer::create([
        'customer_nama' => 'Customer Test 4',
        'customer_telepon' => '081234567893',
    ]);

    // Stock: 10kg product A, 8kg product B
    $this->stockA = Stock::create([
        'stock_id_product' => $this->productA->product_id,
        'stock_code_lokasi' => $this->lokasiRack->lokasi_code,
        'stock_qty' => 10,
        'stock_type' => Stock::TYPE_IN,
        'stock_expired_date' => now()->addDays(30),
    ]);

    $this->stockB = Stock::create([
        'stock_id_product' => $this->productB->product_id,
        'stock_code_lokasi' => $this->lokasiRack->lokasi_code,
        'stock_qty' => 8,
        'stock_type' => Stock::TYPE_IN,
        'stock_expired_date' => now()->addDays(30),
    ]);

    config(['scan.prefix.pallet' => 'P', 'scan.prefix.location' => 'L', 'scan.prefix.barcode' => 'B']);
});

it('Scenario 4: 2 SOs different products - independent tracking', function () {
    // ============================================
    // STEP 1: Create SO A (3kg product A) and SO B (5kg product B)
    // ============================================
    $responseA = $this->postJson(route('wms-so.postCreate'), [
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'details' => [
            ['so_detail_id_product' => $this->productA->product_id, 'so_detail_qty' => 3],
        ],
    ]);
    $responseA->assertOk();
    $soA = So::latest()->first();

    $responseB = $this->postJson(route('wms-so.postCreate'), [
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'details' => [
            ['so_detail_id_product' => $this->productB->product_id, 'so_detail_qty' => 5],
        ],
    ]);
    $responseB->assertOk();
    $soB = So::latest()->first();

    // Verify RESERVE stocks
    $reserveA = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $soA->so_code)
        ->where('stock_id_product', $this->productA->product_id)
        ->first();

    $reserveB = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $soB->so_code)
        ->where('stock_id_product', $this->productB->product_id)
        ->first();

    expect($reserveA->stock_qty)->toBe(3.0);
    expect($reserveB->stock_qty)->toBe(5.0);

    // ============================================
    // STEP 2: Prepare SO A (direct via Sales Order)
    // ============================================
    $soDetailA = $soA->details()->first();

    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$soA->so_id],
        'details' => [
            [
                'so_detail_id' => $soDetailA->so_detail_id,
                'product_id' => $this->productA->product_id,
                'qty' => 3,
            ],
        ],
    ]);
    $response->assertRedirect(route('wms-so.getTable'));

    // Process SO A via SoPrepareScan
    Livewire::test(SoPrepareScan::class, ['soId' => $soA->so_id])
        ->call('scan', $this->stockA->stock_code)
        ->assertSet('successMsg', fn ($msg) => str_contains($msg, 'Scan berhasil'));

    // Verify product A stock decremented
    $this->stockA->refresh();
    expect($this->stockA->stock_qty)->toBe(7.0); // 10 - 3 = 7

    // Verify product B stock unchanged
    $this->stockB->refresh();
    expect($this->stockB->stock_qty)->toBe(8.0); // Unchanged

    // Verify RESERVE A consumed
    $reserveA->refresh();
    expect($reserveA->stock_qty)->toBe(0.0);

    // Verify RESERVE B unchanged
    $reserveB->refresh();
    expect($reserveB->stock_qty)->toBe(5.0);

    // SO A confirmed
    $soA->refresh();
    expect($soA->so_status)->toBe(SoStatusEnum::CONFIRMED);

    // ============================================
    // STEP 3: Prepare SO B (via Keluar)
    // ============================================
    $soDetailB = $soB->details()->first();

    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$soB->so_id],
        'details' => [
            [
                'so_detail_id' => $soDetailB->so_detail_id,
                'product_id' => $this->productB->product_id,
                'qty' => 5,
            ],
        ],
    ]);
    $response->assertRedirect(route('wms-so.getTable'));

    $keluarB = Keluar::where('out_reff', 'Prepare SO')->latest()->first();
    $keluarDetailB = KeluarDetail::where('out_detail_code_keluar', $keluarB->out_code)->first();

    // Assign stock for SO B
    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $soB->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $keluarDetailB->out_detail_id,
                'stock_id' => $this->stockB->stock_id,
                'qty' => 5,
                'so_detail_id' => $soDetailB->so_detail_id,
            ],
        ],
    ]);
    $response->assertRedirect();

    // Create staging stock for SO B
    $stagingB = Stock::create([
        'stock_id_product' => $this->productB->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 5,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_reff' => $keluarB->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    // Scan staging for SO B
    Livewire::test(KeluarRealisasiScan::class, ['detailId' => $keluarDetailB->out_detail_id])
        ->call('scan', $stagingB->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock dialokasikan ke keluar.');

    // ============================================
    // STEP 4: Verify final state
    // ============================================
    // Product A: IN=7 (10-3), RESERVE=0
    $this->stockA->refresh();
    expect($this->stockA->stock_qty)->toBe(7.0);

    $reserveA->refresh();
    expect($reserveA->stock_qty)->toBe(0.0);

    // Product B: IN=8 (unchanged), RESERVE=0, STAGING=0
    $this->stockB->refresh();
    expect($this->stockB->stock_qty)->toBe(8.0);

    $reserveB->refresh();
    expect($reserveB->stock_qty)->toBe(0.0);

    $stagingB->refresh();
    expect($stagingB->stock_qty)->toBe(0.0);

    // Both SOs confirmed
    $soA->refresh();
    $soB->refresh();
    expect($soA->so_status)->toBe(SoStatusEnum::CONFIRMED);
    expect($soB->so_status)->toBe(SoStatusEnum::CONFIRMED);

    // Keluar B done
    $keluarB->refresh();
    expect($keluarB->out_status)->toBe(Keluar::STATUS_DONE);

    // Product A and B stock are independent
    $totalInA = Stock::where('stock_type', Stock::TYPE_IN)
        ->where('stock_id_product', $this->productA->product_id)
        ->sum('stock_qty');
    $totalInB = Stock::where('stock_type', Stock::TYPE_IN)
        ->where('stock_id_product', $this->productB->product_id)
        ->sum('stock_qty');

    expect($totalInA)->toBe(7.0);  // 10 - 3
    expect($totalInB)->toBe(8.0);  // Unchanged
});
