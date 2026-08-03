<?php

use App\Models\Customer;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\User;
use App\Wms\SoStatusEnum;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'SO Tester',
        'email'    => 'so-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));

    $this->customer = Customer::create([
        'customer_nama'    => 'Cust-'.uniqid(),
        'customer_telepon' => '081234567890',
    ]);

    $this->productA = Product::create(['product_nama' => 'Product A', 'product_harga' => 100]);
    $this->productB = Product::create(['product_nama' => 'Product B', 'product_harga' => 200]);
});

it('creates keluar with out_qty and one keluar_detail per SO detail', function () {
    $so1 = So::create([
        'so_tanggal'     => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status'      => 'Pending',
    ]);
    $so1Detail1 = SoDetail::create([
        'so_detail_id_so'       => $so1->so_id,
        'so_detail_id_product'  => $this->productA->product_id,
        'so_detail_qty'         => 50,
        'so_detail_harga'       => 100,
        'so_detail_code'        => $so1->so_code.'-001',
    ]);
    $so1Detail2 = SoDetail::create([
        'so_detail_id_so'       => $so1->so_id,
        'so_detail_id_product'  => $this->productB->product_id,
        'so_detail_qty'         => 30,
        'so_detail_harga'       => 200,
        'so_detail_code'        => $so1->so_code.'-002',
    ]);

    $so2 = So::create([
        'so_tanggal'     => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status'      => 'Pending',
    ]);
    $so2Detail1 = SoDetail::create([
        'so_detail_id_so'       => $so2->so_id,
        'so_detail_id_product'  => $this->productA->product_id,
        'so_detail_qty'         => 40,
        'so_detail_harga'       => 100,
        'so_detail_code'        => $so2->so_code.'-001',
    ]);

    $response = $this->post(route('wms-so-prepare.store'), [
        'so_ids' => [$so1->so_id, $so2->so_id],
        'details' => [
            ['so_detail_id' => $so1Detail1->so_detail_id, 'product_id' => $this->productA->product_id, 'qty' => 50],
            ['so_detail_id' => $so1Detail2->so_detail_id, 'product_id' => $this->productB->product_id, 'qty' => 30],
            ['so_detail_id' => $so2Detail1->so_detail_id, 'product_id' => $this->productA->product_id, 'qty' => 40],
        ],
    ]);

    $response->assertRedirect(route('wms-so.getTable'));
    $response->assertSessionHasNoErrors();

    // Keluar created with total qty
    $keluar = Keluar::latest()->first();
    expect($keluar)->not->toBeNull()
        ->and($keluar->out_qty)->toBe(120.0)
        ->and($keluar->out_reff)->toBe('Prepare SO')
        ->and($keluar->out_status)->toBe('Pending');

    // 3 keluar_details (one per SO detail)
    $details = KeluarDetail::where('out_detail_code_keluar', $keluar->out_code)->get();
    expect($details)->toHaveCount(3);

    // First detail: SO1, Product A, 50kg
    expect($details[0]->out_detail_id_so_detail)->toBe($so1Detail1->so_detail_id)
        ->and($details[0]->out_detail_id_product)->toBe($this->productA->product_id)
        ->and($details[0]->out_detail_qty)->toBe(50.0)
        ->and($details[0]->out_detail_reff)->toBe($so1Detail1->so_detail_code);

    // Second detail: SO1, Product B, 30kg
    expect($details[1]->out_detail_id_so_detail)->toBe($so1Detail2->so_detail_id)
        ->and($details[1]->out_detail_qty)->toBe(30.0)
        ->and($details[1]->out_detail_reff)->toBe($so1Detail2->so_detail_code);

    // Third detail: SO2, Product A, 40kg
    expect($details[2]->out_detail_id_so_detail)->toBe($so2Detail1->so_detail_id)
        ->and($details[2]->out_detail_qty)->toBe(40.0)
        ->and($details[2]->out_detail_reff)->toBe($so2Detail1->so_detail_code);

    // Both SOs status changed to Prepare
    expect($so1->fresh()->so_status)->toBe(SoStatusEnum::PREPARE)
        ->and($so2->fresh()->so_status)->toBe(SoStatusEnum::PREPARE);
});

it('getPrepare shows per-SO detail rows', function () {
    $so1 = So::create([
        'so_tanggal'     => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status'      => 'Pending',
    ]);
    $so1Detail = SoDetail::create([
        'so_detail_id_so'       => $so1->so_id,
        'so_detail_id_product'  => $this->productA->product_id,
        'so_detail_qty'         => 60,
        'so_detail_harga'       => 100,
        'so_detail_code'        => $so1->so_code.'-001',
    ]);

    $so2 = So::create([
        'so_tanggal'     => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status'      => 'Pending',
    ]);
    $so2Detail = SoDetail::create([
        'so_detail_id_so'       => $so2->so_id,
        'so_detail_id_product'  => $this->productA->product_id,
        'so_detail_qty'         => 40,
        'so_detail_harga'       => 100,
        'so_detail_code'        => $so2->so_code.'-001',
    ]);

    // Verify preconditions
    $allSos = So::pluck('so_id')->toArray();
    $allDetails = SoDetail::pluck('so_detail_id_so')->toArray();

    $response = $this->get(route('wms-so-prepare.create', ['so_ids' => [$so1->so_id, $so2->so_id]]));
    $response->assertOk();

    $viewData = $response->getOriginalContent()->getData();
    $detailRows = $viewData['detailRows'];

    expect($detailRows)->toHaveCount(2)
        ->and($detailRows[0]['so_detail_id'])->toBe($so1Detail->so_detail_id)
        ->and($detailRows[0]['so_code'])->toBe($so1->so_code)
        ->and($detailRows[0]['qty'])->toBe(60)
        ->and($detailRows[1]['so_detail_id'])->toBe($so2Detail->so_detail_id)
        ->and($detailRows[1]['so_code'])->toBe($so2->so_code)
        ->and($detailRows[1]['qty'])->toBe(40);
});

it('keluarCodeForSo finds correct keluar via FK', function () {
    $so = So::create([
        'so_tanggal'     => now()->toDateString(),
        'so_id_customer' => $this->customer->customer_id,
        'so_status'      => 'Pending',
    ]);
    $soDetail = SoDetail::create([
        'so_detail_id_so'       => $so->so_id,
        'so_detail_id_product'  => $this->productA->product_id,
        'so_detail_qty'         => 25,
        'so_detail_harga'       => 100,
        'so_detail_code'        => $so->so_code.'-001',
    ]);

    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status'  => 'Pending',
        'out_reff'    => 'Prepare SO',
        'out_qty'     => 25,
    ]);
    $detail = KeluarDetail::create([
        'out_detail_code_keluar'    => $keluar->out_code,
        'out_detail_id_product'     => $this->productA->product_id,
        'out_detail_id_so_detail'   => $soDetail->so_detail_id,
        'out_detail_code'           => $keluar->out_code.'-001',
        'out_detail_qty'            => 25,
        'out_detail_reff'           => $soDetail->so_detail_code,
    ]);

    // FK relationship works
    expect($detail->soDetail)->not->toBeNull()
        ->and($detail->soDetail->so_detail_id)->toBe($soDetail->so_detail_id)
        ->and($detail->soDetail->so->so_id)->toBe($so->so_id);

    // Find keluar via FK
    $found = KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
        ->value('out_detail_code_keluar');
    expect($found)->toBe($keluar->out_code);
});
