<?php

use App\Models\Customer;
use App\Models\ForkliftTask;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Models\User;
use App\Wms\MasukStatusEnum;
use App\Wms\SoStatusEnum;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'Forklift Task Tester',
        'email'    => 'forklift-task-' . uniqid() . '@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));

    $this->gudang = Gudang::create([
        'gudang_code' => 'GDG-A',
        'gudang_nama' => 'Gudang A',
    ]);

    $this->rak = Lokasi::create([
        'lokasi_code'         => 'RAK-A1',
        'lokasi_nama'         => 'Rak A1',
        'lokasi_category'     => 'Rack',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);

    $this->staging = Lokasi::create([
        'lokasi_code'         => 'STG-01',
        'lokasi_nama'         => 'Staging 01',
        'lokasi_category'     => 'staging',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);

    $this->product = Product::create([
        'product_nama'  => 'Produk Forklift Task',
        'product_harga' => 10000,
    ]);

    $this->customer = Customer::create([
        'customer_nama'    => 'Customer Forklift Task',
        'customer_telepon' => '081234567890',
    ]);

    config(['scan.prefix_pallet' => 'P']);
    config(['scan.prefix_location' => 'L']);
});

test('create putaway task on ready', function () {
    $masukDetail = MasukDetail::create([
        'in_detail_code'       => 'IN-TEST-001',
        'in_detail_id_product' => $this->product->product_id,
        'in_detail_id_lokasi'  => $this->rak->lokasi_code,
        'in_detail_id_staging' => $this->staging->lokasi_code,
        'in_detail_qty'        => 10,
        'in_detail_tanggal'    => now()->toDateString(),
        'in_detail_status'     => MasukStatusEnum::PROCESS,
    ]);

    $groupCode = 'PAL-TEST-001';
    MasukRealisasi::create([
        'in_realisasi_masuk_code' => $masukDetail->in_detail_code,
        'in_realisasi_id_product' => $this->product->product_id,
        'in_realisasi_qty'        => 10,
        'in_realisasi_code_lokasi' => $this->staging->lokasi_code,
        'in_realisasi_group'      => $groupCode,
    ]);

    $masukDetail->update(['in_detail_status' => MasukStatusEnum::READY]);

    ForkliftTask::firstOrCreate(
        ['forklift_type' => 'putaway', 'forklift_pallet_code' => $groupCode],
        [
            'forklift_lokasi_asal'   => $this->staging->lokasi_code,
            'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
            'forklift_reff'          => $masukDetail->in_detail_code,
            'forklift_status'        => 'Pending',
        ]
    );

    $this->assertDatabaseHas('forklift_task', [
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => $groupCode,
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => $masukDetail->in_detail_code,
        'forklift_status'      => 'Pending',
    ]);
});

test('scan pallet locks task', function () {
    $task = ForkliftTask::create([
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => 'PLT-001',
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => 'IN-TEST-002',
        'forklift_status'      => 'Pending',
    ]);

    $response = $this->postJson(route('wms-forklift-task.scan'), [
        'code' => 'PPLT-001',
    ]);

    $response->assertOk();
    expect($response->json('ok'))->toBeTrue();
    expect($response->json('task_type'))->toBe('putaway');
    expect($response->json('next_scan'))->toBe('location');

    $this->assertDatabaseHas('forklift_task', [
        'forklift_id'     => $task->forklift_id,
        'forklift_status' => ForkliftTask::STATUS_PROGRESS,
        'forklift_operator' => 'Forklift Task Tester',
    ]);
});

test('scan wrong rack rejected', function () {
    $wrongRak = Lokasi::create([
        'lokasi_code'         => 'RAK-Z9',
        'lokasi_nama'         => 'Rak Z9',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);

    $task = ForkliftTask::create([
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => 'PLT-002',
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => 'IN-TEST-003',
        'forklift_status'      => 'Pending',
    ]);

    $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-002'])->assertOk();

    $response = $this->postJson(route('wms-forklift-task.scan'), [
        'code' => 'L' . $wrongRak->lokasi_code,
    ]);

    $response->assertStatus(422);
    expect($response->json('ok'))->toBeFalse();
});

test('scan correct rack completes putaway', function () {
    $stock = Stock::create([
        'stock_id_product'  => $this->product->product_id,
        'stock_qty'         => 20,
        'stock_type'        => Stock::TYPE_STAGING,
        'stock_code_lokasi' => $this->staging->lokasi_code,
        'stock_reff'        => 'PLT-003',
        'stock_pallet_code' => 'PLT-003',
    ]);

    $task = ForkliftTask::create([
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => 'PLT-003',
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => 'IN-TEST-004',
        'forklift_status'      => 'Pending',
    ]);

    $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-003'])->assertOk();

    $response = $this->postJson(route('wms-forklift-task.scan'), [
        'code' => 'L' . $this->rak->lokasi_code,
    ]);

    $response->assertOk();
    expect($response->json('ok'))->toBeTrue();

    $this->assertDatabaseHas('stock', [
        'stock_id'          => $stock->stock_id,
        'stock_type'        => Stock::TYPE_IN,
        'stock_code_lokasi' => $this->rak->lokasi_code,
    ]);

    $this->assertDatabaseHas('forklift_task', [
        'forklift_id'          => $task->forklift_id,
        'forklift_status'      => ForkliftTask::STATUS_DONE,
        'forklift_lokasi_final' => $this->rak->lokasi_code,
    ]);
});

test('create pick task on keluar prepare', function () {
    $so = So::create([
        'so_id_customer' => $this->customer->customer_id,
        'so_tanggal'     => now()->toDateString(),
        'so_status'      => SoStatusEnum::PREPARE,
    ]);
    $soDetail = SoDetail::create([
        'so_detail_id_so'       => $so->so_id,
        'so_detail_id_product'  => $this->product->product_id,
        'so_detail_qty'         => 20,
        'so_detail_code'        => $so->so_code . '-001',
    ]);

    $keluar = Keluar::create([
        'out_tanggal' => now()->toDateString(),
        'out_status'  => 'Pending',
        'out_reff'    => 'Prepare SO',
        'out_qty'     => 20,
    ]);
    $keluarDetail = KeluarDetail::create([
        'out_detail_code_keluar'  => $keluar->out_code,
        'out_detail_id_product'   => $this->product->product_id,
        'out_detail_id_so_detail' => $soDetail->so_detail_id,
        'out_detail_code'         => $keluar->out_code . '-001',
        'out_detail_qty'          => 20,
    ]);

    $stock = Stock::create([
        'stock_id_product'   => $this->product->product_id,
        'stock_qty'          => 20,
        'stock_type'         => Stock::TYPE_IN,
        'stock_code_lokasi'  => $this->rak->lokasi_code,
        'stock_pallet_code'  => 'PLT-010',
    ]);

    StockAssignment::create([
        'stock_assignment_id_keluar'        => $keluar->out_code,
        'stock_assignment_id_stock'         => $stock->stock_id,
        'stock_assignment_id_keluar_detail' => $keluarDetail->out_detail_id,
        'stock_assignment_id_so_detail'     => $soDetail->so_detail_id,
        'stock_assignment_qty'              => 20,
        'stock_assignment_status'           => 'Pending',
    ]);

    $response = $this->post(route('wms-keluar-prepare.update', ['outCode' => $keluar->out_code]), [
        'assign' => [
            $keluarDetail->out_detail_id => [
                ['stock_id' => $stock->stock_id, 'keluar_detail_id' => $keluarDetail->out_detail_id, 'qty' => 20],
            ],
        ],
    ]);

    $response->assertSessionDoesntHaveErrors();

    $this->assertDatabaseHas('stock_assignment', [
        'stock_assignment_id_keluar'        => $keluar->out_code,
        'stock_assignment_id_stock'         => $stock->stock_id,
        'stock_assignment_id_keluar_detail' => $keluarDetail->out_detail_id,
        'stock_assignment_qty'              => 20,
    ]);

    ForkliftTask::firstOrCreate(
        ['forklift_type' => 'pick', 'forklift_pallet_code' => 'PLT-010', 'forklift_reff' => $keluar->out_code],
        [
            'forklift_type'          => 'pick',
            'forklift_pallet_code'   => 'PLT-010',
            'forklift_lokasi_asal'   => $this->rak->lokasi_code,
            'forklift_lokasi_tujuan' => $this->staging->lokasi_code,
            'forklift_reff'          => $keluar->out_code,
            'forklift_status'        => 'Pending',
        ]
    );

    $this->assertDatabaseHas('forklift_task', [
        'forklift_type'        => 'pick',
        'forklift_pallet_code' => 'PLT-010',
        'forklift_reff'        => $keluar->out_code,
        'forklift_status'      => 'Pending',
    ]);
});

test('scan pallet locks pick task', function () {
    $task = ForkliftTask::create([
        'forklift_type'        => 'pick',
        'forklift_pallet_code' => 'PLT-PICK-001',
        'forklift_lokasi_asal'   => $this->rak->lokasi_code,
        'forklift_lokasi_tujuan' => $this->staging->lokasi_code,
        'forklift_reff'        => 'OUT-TEST-001',
        'forklift_status'      => 'Pending',
    ]);

    $response = $this->postJson(route('wms-forklift-task.scan'), [
        'code' => 'PPLT-PICK-001',
    ]);

    $response->assertOk();
    expect($response->json('ok'))->toBeTrue();
    expect($response->json('task_type'))->toBe('pick');

    $this->assertDatabaseHas('forklift_task', [
        'forklift_id'     => $task->forklift_id,
        'forklift_status' => ForkliftTask::STATUS_PROGRESS,
        'forklift_operator' => 'Forklift Task Tester',
    ]);
});

test('scan staging completes pick', function () {
    $stock = Stock::create([
        'stock_id_product'   => $this->product->product_id,
        'stock_qty'          => 15,
        'stock_type'         => Stock::TYPE_IN,
        'stock_code_lokasi'  => $this->rak->lokasi_code,
        'stock_reff'         => 'PLT-PICK-002',
        'stock_pallet_code'  => 'PLT-PICK-002',
    ]);

    ForkliftTask::create([
        'forklift_type'        => 'pick',
        'forklift_pallet_code' => 'PLT-PICK-002',
        'forklift_lokasi_asal'   => $this->rak->lokasi_code,
        'forklift_lokasi_tujuan' => $this->staging->lokasi_code,
        'forklift_reff'        => 'OUT-TEST-002',
        'forklift_status'      => 'Pending',
    ]);

    $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-PICK-002'])->assertOk();

    $response = $this->postJson(route('wms-forklift-task.scan'), [
        'code' => 'L' . $this->staging->lokasi_code,
    ]);

    $response->assertOk();
    expect($response->json('ok'))->toBeTrue();

    $this->assertDatabaseHas('stock', [
        'stock_id'          => $stock->stock_id,
        'stock_type'        => Stock::TYPE_STAGING,
        'stock_code_lokasi' => $this->staging->lokasi_code,
    ]);

    $this->assertDatabaseHas('forklift_task', [
        'forklift_pallet_code' => 'PLT-PICK-002',
        'forklift_status'      => ForkliftTask::STATUS_DONE,
        'forklift_lokasi_final' => $this->staging->lokasi_code,
    ]);
});

test('card locking', function () {
    $operatorA = User::create([
        'name'     => 'Operator A',
        'email'    => 'op-a-' . uniqid() . '@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]);
    $operatorB = User::create([
        'name'     => 'Operator B',
        'email'    => 'op-b-' . uniqid() . '@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]);

    $task1 = ForkliftTask::create([
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => 'PLT-LOCK-001',
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => 'IN-LOCK-001',
        'forklift_status'      => 'Pending',
    ]);
    $task2 = ForkliftTask::create([
        'forklift_type'        => 'putaway',
        'forklift_pallet_code' => 'PLT-LOCK-002',
        'forklift_lokasi_asal'   => $this->staging->lokasi_code,
        'forklift_lokasi_tujuan' => $this->rak->lokasi_code,
        'forklift_reff'        => 'IN-LOCK-002',
        'forklift_status'      => 'Pending',
    ]);

    $this->actingAs($operatorA);
    $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-LOCK-001'])->assertOk();

    $this->actingAs($operatorB);
    $responseTask1 = $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-LOCK-001']);
    $responseTask1->assertStatus(422);

    $responseTask2 = $this->postJson(route('wms-forklift-task.scan'), ['code' => 'PPLT-LOCK-002']);
    $responseTask2->assertOk();

    $this->assertDatabaseHas('forklift_task', [
        'forklift_id'     => $task1->forklift_id,
        'forklift_operator' => 'Operator A',
        'forklift_status' => ForkliftTask::STATUS_PROGRESS,
    ]);
    $this->assertDatabaseHas('forklift_task', [
        'forklift_id'     => $task2->forklift_id,
        'forklift_operator' => 'Operator B',
        'forklift_status' => ForkliftTask::STATUS_PROGRESS,
    ]);
});

test('ready rejected without staging', function () {
    $masukDetail = MasukDetail::create([
        'in_detail_code'       => 'IN-TEST-NOSTG',
        'in_detail_id_product' => $this->product->product_id,
        'in_detail_id_lokasi'  => $this->rak->lokasi_code,
        'in_detail_qty'        => 5,
        'in_detail_tanggal'    => now()->toDateString(),
        'in_detail_status'     => MasukStatusEnum::PROCESS,
    ]);

    $response = $this->post(route('wms-masuk-detail.postRealisasikan', ['id' => $masukDetail->in_detail_code]), [
        'realisasi_qty' => 5,
    ]);

    $response->assertSessionHasErrors();
});

test('staging select available', function () {
    Lokasi::create([
        'lokasi_code'         => 'STG-EXTRA',
        'lokasi_nama'         => 'Staging Extra',
        'lokasi_category'     => 'staging',
        'lokasi_code_gudang'  => $this->gudang->gudang_code,
    ]);

    $stagings = Lokasi::where('lokasi_category', 'staging')->get();

    expect($stagings)->toHaveCount(2);
    expect($stagings->pluck('lokasi_code')->toArray())->toContain('STG-01', 'STG-EXTRA');
});