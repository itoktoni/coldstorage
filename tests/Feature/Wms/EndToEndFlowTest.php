<?php

/**
 * End-to-end WMS flow test:
 * PO Receiving → Stock IN → SO Create (RESERVE) → Prepare SO (Keluar)
 * → Stock Assignment → Pick (scan staging) → Warehouse Prepare → Ship
 */

use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Gudang;
use App\Models\Invoice;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Models\User;
use App\Wms\SoStatusEnum;

beforeEach(function () {
    $this->actingAs(User::create([
        'name' => 'E2E Tester',
        'email' => 'e2e-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role' => 'admin',
        'verified_at' => now(),
    ]));

    // Master data
    $uniqid = uniqid();
    $this->gudang = Gudang::create(['gudang_code' => 'GDG-'.$uniqid, 'gudang_nama' => 'Gudang '.$uniqid]);
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
        'customer_nama' => 'Customer E2E',
        'customer_telepon' => '081234567890',
    ]);

    config(['langkahkecil.verification.register_backend' => false]);
    config(['langkahkecil.bypass.email_verification.web' => true]);
    config(['scan.prefix.pallet' => 'P', 'scan.prefix.location' => 'L', 'scan.prefix.barcode' => 'B']);
});

it('full flow: PO receiving → SO → prepare → pick → ship', function () {
    // ──────────────────────────────────────────────
    // STEP 1: PO Receiving — create inbound + realisasi
    // ──────────────────────────────────────────────
    $masukDetail = MasukDetail::create([
        'in_detail_tanggal' => now()->toDateString(),
        'in_detail_id_product' => $this->product->product_id,
        'in_detail_qty' => 100,
        'in_detail_id_lokasi' => $this->lokasiRack->lokasi_code,
        'in_detail_id_staging' => $this->lokasiStaging->lokasi_code,
        'in_detail_status' => 'pending',
    ]);

    $groupCode = MasukRealisasi::generateGroupCode();

    // Realisasi 100 qty → status becomes READY
    MasukRealisasi::create([
        'in_realisasi_masuk_code' => $masukDetail->in_detail_code,
        'in_realisasi_id_product' => $this->product->product_id,
        'in_realisasi_qty' => 100,
        'in_realisasi_code_lokasi' => $this->lokasiRack->lokasi_code,
        'in_realisasi_group' => $groupCode,
    ]);

    $masukDetail->update(['in_detail_status' => 'ready']);

    // Stock created at rack (TYPE_IN)
    $stock = Stock::create([
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasiRack->lokasi_code,
        'stock_qty' => 100,
        'stock_type' => Stock::TYPE_IN,
        'stock_pallet_code' => $groupCode,
        'stock_expired_date' => now()->addDays(30),
    ]);

    expect($stock->stock_qty)->toBe(100.0);

    // ──────────────────────────────────────────────
    // STEP 2: Create SO — creates RESERVE stock
    // ──────────────────────────────────────────────
    $response = $this->postJson(route('wms-so.postCreate'), [
        'so_tanggal' => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'details' => [
            ['so_detail_id_product' => $this->product->product_id, 'so_detail_qty' => 80],
        ],
    ]);

    $response->assertOk();
    $so = So::latest()->first();
    expect($so->so_status)->toBe(SoStatusEnum::PENDING);

    $soDetail = $so->details()->first();
    expect($soDetail->so_detail_qty)->toBe(80);

    // RESERVE stock created
    $reserve = Stock::where('stock_type', Stock::TYPE_RESERVE)
        ->where('stock_reff', $so->so_code)
        ->where('stock_id_product', $this->product->product_id)
        ->first();
    expect($reserve)->not->toBeNull()
        ->and($reserve->stock_qty)->toBe(80.0);

    // ──────────────────────────────────────────────
    // STEP 3: Prepare SO — creates Keluar + KeluarDetails
    // ──────────────────────────────────────────────
    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$so->so_id],
        'details' => [
            ['so_detail_id' => $soDetail->so_detail_id, 'product_id' => $this->product->product_id, 'qty' => 80],
        ],
    ]);

    $response->assertRedirect(route('wms-so.getTable'));
    $response->assertSessionHasNoErrors();

    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::PREPARE);

    $keluar = Keluar::where('out_reff', 'Prepare SO')->latest()->first();
    expect($keluar)->not->toBeNull()
        ->and($keluar->out_status)->toBe('Pending')
        ->and($keluar->out_qty)->toBe(80.0);

    $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->first();
    expect($keluarDetail)->not->toBeNull()
        ->and($keluarDetail->out_detail_qty)->toBe(80.0)
        ->and($keluarDetail->out_detail_id_product)->toBe($this->product->product_id);

    // ──────────────────────────────────────────────
    // STEP 4: Stock Assignment — assign IN stock to keluar detail
    // ──────────────────────────────────────────────
    $response = $this->post(route('wms-so-prepare.assignStore', ['soId' => $so->so_id]), [
        'assignments' => [
            [
                'keluar_detail_id' => $keluarDetail->out_detail_id,
                'stock_id' => $stock->stock_id,
                'qty' => 80,
                'so_detail_id' => $soDetail->so_detail_id,
            ],
        ],
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $assignment = StockAssignment::where('stock_assignment_id_keluar', $keluar->out_code)->first();
    expect($assignment)->not->toBeNull()
        ->and($assignment->stock_assignment_qty)->toBe(80.0)
        ->and($assignment->stock_assignment_status)->toBe('Pending');

    // ──────────────────────────────────────────────
    // STEP 5: Pick — scan from STAGING, creates KeluarRealisasi
    // ──────────────────────────────────────────────
    // First, create staging stock (simulating forklift moved from rack to staging)
    $stagingStock = Stock::create([
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 80,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_pallet_code' => $groupCode,
        'stock_reff' => $keluar->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    // Scan staging barcode to pick
    $barcode = $stagingStock->stock_code;

    // Simulate KeluarRealisasiScan::scan logic
    $scannedStock = Stock::where('stock_code', $barcode)
        ->where('stock_type', Stock::TYPE_STAGING)
        ->where('stock_qty', '>', 0)
        ->lockForUpdate()
        ->first();

    expect($scannedStock)->not->toBeNull()
        ->and($scannedStock->stock_id_product)->toBe($this->product->product_id);

    $alreadyPicked = (float) KeluarRealisasi::where('out_realisasi_id_detail', $keluarDetail->out_detail_id)
        ->sum('out_realisasi_qty');
    $remaining = (float) $keluarDetail->out_detail_qty - $alreadyPicked;
    expect($remaining)->toBe(80.0);

    $take = min((float) $scannedStock->stock_qty, $remaining);
    $scannedStock->decrement('stock_qty', $take);

    $realisasi = KeluarRealisasi::create([
        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
        'out_realisasi_id_stock' => $scannedStock->stock_id,
        'out_realisasi_qty' => $take,
    ]);

    expect($realisasi)->not->toBeNull()
        ->and($realisasi->out_realisasi_qty)->toBe(80.0);

    // Consume RESERVE
    Stock::consumeReserve($so->so_code, $this->product->product_id, $take);

    $reserve->refresh();
    expect($reserve->stock_qty)->toBe(0.0);

    // Keluar status → Done
    $keluar->update(['out_status' => Keluar::STATUS_DONE]);
    $keluar->refresh();
    expect($keluar->out_status)->toBe(Keluar::STATUS_DONE);

    // ──────────────────────────────────────────────
    // STEP 6: Warehouse Prepare — scan staging stock via Livewire
    // ──────────────────────────────────────────────
    // Create fresh staging stock for so-prepare scan
    $stagingForPrepare = Stock::create([
        'stock_id_product' => $this->product->product_id,
        'stock_code_lokasi' => $this->lokasiStaging->lokasi_code,
        'stock_qty' => 80,
        'stock_type' => Stock::TYPE_STAGING,
        'stock_reff' => $keluar->out_code,
        'stock_expired_date' => now()->addDays(30),
    ]);

    // Simulate SoPrepareScan::scan logic
    $soPrepare = SoPrepare::firstOrCreate(
        ['so_prepare_id_so' => $so->so_id],
        ['so_prepare_id_keluar' => $keluar->out_code]
    );

    $line = $so->details->firstWhere('so_detail_id_product', $stagingForPrepare->stock_id_product);
    expect($line)->not->toBeNull();

    $assignedForLine = (float) $soPrepare->details()
        ->where('so_prepare_detail_id_product', $stagingForPrepare->stock_id_product)
        ->sum('so_prepare_detail_qty');
    $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;
    expect($lineRemaining)->toBe(80.0);

    $qty = min($lineRemaining, (float) $stagingForPrepare->stock_qty);

    $soRealisasi = KeluarRealisasi::create([
        'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
        'out_realisasi_id_stock' => $stagingForPrepare->stock_id,
        'out_realisasi_qty' => $qty,
    ]);

    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare' => $soPrepare->so_prepare_id,
        'so_prepare_detail_id_realisasi' => $soRealisasi->out_realisasi_id,
        'so_prepare_detail_id_product' => $stagingForPrepare->stock_id_product,
        'so_prepare_detail_id_stock' => $stagingForPrepare->stock_id,
        'so_prepare_detail_qty' => $qty,
    ]);

    Stock::where('stock_id', $stagingForPrepare->stock_id)->decrement('stock_qty', $qty);

    // Check fulfillment → all lines done → SO Confirmed
    $totalAssigned = (float) $soPrepare->details()
        ->where('so_prepare_detail_id_product', $line->so_detail_id_product)
        ->sum('so_prepare_detail_qty');
    expect($totalAssigned)->toBe(80.0);

    $soPrepare->update(['so_prepare_status' => SoPrepare::STATUS_DONE]);
    $so->update(['so_status' => SoStatusEnum::CONFIRMED]);
    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::CONFIRMED);

    // ──────────────────────────────────────────────
    // STEP 7: Ship — create Invoice + Delivery Order
    // ──────────────────────────────────────────────
    $response = $this->post(route('wms-so.storeShip', ['id' => $so->so_id]), [
        'delivery_nama_penerima' => 'Penerima E2E',
        'delivery_alamat_tujuan' => 'Jl. Test No. 1',
        'delivery_nama_driver' => 'Driver E2E',
        'delivery_plat_kendaraan' => 'B 1234 E2E',
    ]);

    $response->assertRedirect(route('wms-so.getTable'));
    $response->assertSessionHasNoErrors();

    $so->refresh();
    expect($so->so_status)->toBe(SoStatusEnum::SHIPPED);

    // Invoice created
    $invoice = Invoice::where('invoice_id_so', $so->so_id)->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->invoice_subtotal)->toEqual(80 * 50000) // 80 qty × 50000 harga
        ->and((float) $invoice->invoice_ppn)->toEqual(80 * 50000 * 0.11)
        ->and((float) $invoice->invoice_total)->toEqual(80 * 50000 + 80 * 50000 * 0.11)
        ->and($invoice->invoice_status)->toBe('Unpaid');

    // Invoice detail
    $invoiceDetail = $invoice->details()->first();
    expect($invoiceDetail)->not->toBeNull()
        ->and((float) $invoiceDetail->invoice_detail_qty)->toEqual(80.0)
        ->and((float) $invoiceDetail->invoice_detail_harga)->toEqual(50000);

    // Delivery created
    $delivery = Delivery::where('delivery_id_so', $so->so_id)->first();
    expect($delivery)->not->toBeNull()
        ->and($delivery->delivery_nama_penerima)->toBe('Penerima E2E')
        ->and($delivery->delivery_id_invoice)->toBe($invoice->invoice_id)
        ->and($delivery->delivery_status)->toBe('Pending');
});
