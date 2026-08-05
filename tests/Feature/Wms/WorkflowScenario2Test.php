<?php

/**
 * Scenario 2: 1 SO with 2 Products (different)
 *
 * Flow: Stock 10kg product A, 8kg product B → SO 3kg A + 5kg B → Prepare → Keluar → Scan → Verify
 */

use App\Livewire\KeluarRealisasiScan;
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
        'name' => 'Test User 2',
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
        'customer_nama' => 'Customer Test 2',
        'customer_telepon' => '081234567891',
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

it('Scenario 2: 1 SO 2 Products - full workflow', function () {
    // ============================================
    // STEP 1: Create SO with 2 products
    // ============================================
    $response = $this->postJson(route('wms-so.postCreate'), [
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'details' => [
            ['so_detail_id_product' => $this->productA->product_id, 'so_detail_qty' => 3],
            ['so_detail_id_product' => $this->productB->product_id, 'so_detail_qty' => 5],
        ],
    ]);

    $response->assertOk();
    $so = So::latest()->first();

    // Verify RESERVE stocks created for both products
    $reserveA = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $so->so_code)
        ->where('stock_id_product', $this->productA->product_id)
        ->first();

    $reserveB = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $so->so_code)
        ->where('stock_id_product', $this->productB->product_id)
        ->first();

    expect($reserveA)->not->toBeNull()
        ->and($reserveA->stock_qty)->toBe(3.0);
    expect($reserveB)->not->toBeNull()
        ->and($reserveB->stock_qty)->toBe(5.0);

    // ============================================
    // STEP 2: Prepare SO
    // ============================================
    $soDetails = $so->details()->get();

    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$so->so_id],
        'details' => $soDetails->map(fn ($d) => [
            'so_detail_id' => $d->so_detail_id,
            'product_id' => $d->so_detail_id_product,
            'qty' => $d->so_detail_qty,
        ])->toArray(),
    ]);

    $response->assertRedirect(route('wms-so.getTable'));

    $keluar = Keluar::where('out_reff', 'Prepare SO')->latest()->first();
    expect($keluar)->not->toBeNull()
        ->and($keluar->out_qty)->toBe(8.0); // 3 + 5

    $keluarDetails = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
    expect($keluarDetails)->toHaveCount(2);

    // ============================================
    // STEP 3: Assign stock for both products
    // ============================================
    $kdA = $keluarDetails->first(fn ($d) => $d->out_detail_id_product === $this->productA->product_id);
    $kdB = $keluarDetails->first(fn ($d) => $d->out_detail_id_product === $this->productB->product_id);

    $soDetailA = $soDetails->first(fn ($d) => $d->so_detail_id_product === $this->productA->product_id);
    $soDetailB = $soDetails->first(fn ($d) => $d->so_detail_id_product === $this->productB->product_id);

    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $kdA->out_detail_id,
                'stock_id' => $this->stockA->stock_id,
                'qty' => 3,
                'so_detail_id' => $soDetailA->so_detail_id,
            ],
            [
                'keluar_detail_id' => $kdB->out_detail_id,
                'stock_id' => $this->stockB->stock_id,
                'qty' => 5,
                'so_detail_id' => $soDetailB->so_detail_id,
            ],
        ],
    ]);

    $response->assertRedirect();

    // ============================================
    // STEP 4: Scan staging for product A
    // ============================================
    $stagingA = Stock::create([
        'stock_id_product' => $this->productA->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 3,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_reff' => $keluar->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    Livewire::test(KeluarRealisasiScan::class, ['detailId' => $kdA->out_detail_id])
        ->call('scan', $stagingA->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock dialokasikan ke keluar.');

    // ============================================
    // STEP 5: Scan staging for product B
    // ============================================
    $stagingB = Stock::create([
        'stock_id_product' => $this->productB->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 5,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_reff' => $keluar->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    Livewire::test(KeluarRealisasiScan::class, ['detailId' => $kdB->out_detail_id])
        ->call('scan', $stagingB->stock_code)
        ->assertSet('successMsg', 'Scan berhasil. Stock dialokasikan ke keluar.');

    // ============================================
    // STEP 6: Verify final state
    // ============================================
    $this->stockA->refresh();
    $this->stockB->refresh();

    // IN stock unchanged (picked from staging)
    expect($this->stockA->stock_qty)->toBe(10.0);
    expect($this->stockB->stock_qty)->toBe(8.0);

    // RESERVE consumed
    $reserveA->refresh();
    $reserveB->refresh();
    expect($reserveA->stock_qty)->toBe(0.0);
    expect($reserveB->stock_qty)->toBe(0.0);

    // Staging consumed
    $stagingA->refresh();
    $stagingB->refresh();
    expect($stagingA->stock_qty)->toBe(0.0);
    expect($stagingB->stock_qty)->toBe(0.0);

    // Keluar Done
    $keluar->refresh();
    expect($keluar->out_status)->toBe(Keluar::STATUS_DONE);

    // SO Confirmed
    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::CONFIRMED);
});
