<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Kendaraan;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Supir;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->product = Product::create([
        'product_code' => 'P01',
        'product_nama' => 'Kentang',
        'product_kategori' => 'sayuran',
        'product_harga' => 100000,
    ]);
});

it('shows ship form for confirmed so with prepare', function () {
    $customer = Customer::create(['customer_nama' => 'Test Customer', 'customer_alamat' => 'Jl. Test']);
    $kendaraan = Kendaraan::create(['kendaraan_nama' => 'Truck', 'kendaraan_plat' => 'B 1234 AB']);
    $supir = Supir::create(['supir_nama' => 'Supir Test', 'supir_telp' => '081234']);

    $so = So::create([
        'so_code' => 'SO-TEST-001',
        'so_tanggal' => now(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => 'Confirmed',
    ]);

    SoDetail::create([
        'so_detail_id_so' => $so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 10,
        'so_detail_harga' => 100000,
        'so_detail_code' => 'SOD-TEST-001',
    ]);

    $prepare = SoPrepare::create([
        'so_prepare_id_so' => $so->so_id,
        'so_prepare_code' => 'PREP-TEST-001',
        'so_prepare_status' => 'Done',
    ]);

    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
        'so_prepare_detail_id_product' => $this->product->product_id,
        'so_prepare_detail_qty' => 5,
    ]);

    $response = $this->get(route('wms-so.ship', $so->so_id));

    $response->assertOk();
    $response->assertSee($kendaraan->kendaraan_nama);
    $response->assertSee($supir->supir_nama);
});

it('validates kendaraan and supir exist', function () {
    $customer = Customer::create(['customer_nama' => 'Test Customer', 'customer_alamat' => 'Jl. Test']);

    $so = So::create([
        'so_code' => 'SO-TEST-002',
        'so_tanggal' => now(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => 'Confirmed',
    ]);

    SoDetail::create([
        'so_detail_id_so' => $so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 10,
        'so_detail_harga' => 100000,
        'so_detail_code' => 'SOD-TEST-002',
    ]);

    $prepare = SoPrepare::create([
        'so_prepare_id_so' => $so->so_id,
        'so_prepare_code' => 'PREP-TEST-002',
        'so_prepare_status' => 'Done',
    ]);

    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
        'so_prepare_detail_id_product' => $this->product->product_id,
        'so_prepare_detail_qty' => 5,
    ]);

    $response = $this->post(route('wms-so.storeShip', $so->so_id), [
        'delivery_id_kendaraan' => 999,
        'delivery_id_supir' => 999,
    ]);

    $response->assertSessionHasErrors(['delivery_id_kendaraan', 'delivery_id_supir']);
});

it('creates delivery with kendaraan and supir', function () {
    $customer = Customer::create(['customer_nama' => 'Test Customer', 'customer_alamat' => 'Jl. Test']);
    $kendaraan = Kendaraan::create(['kendaraan_nama' => 'Truck', 'kendaraan_plat' => 'B 1234 AB']);
    $supir = Supir::create(['supir_nama' => 'Supir Test', 'supir_telp' => '081234']);

    $so = So::create([
        'so_code' => 'SO-TEST-003',
        'so_tanggal' => now(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => 'Confirmed',
    ]);

    SoDetail::create([
        'so_detail_id_so' => $so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 10,
        'so_detail_harga' => 100000,
        'so_detail_code' => 'SOD-TEST-003',
    ]);

    $prepare = SoPrepare::create([
        'so_prepare_id_so' => $so->so_id,
        'so_prepare_code' => 'PREP-TEST-003',
        'so_prepare_status' => 'Done',
    ]);

    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
        'so_prepare_detail_id_product' => $this->product->product_id,
        'so_prepare_detail_qty' => 5,
    ]);

    $response = $this->post(route('wms-so.storeShip', $so->so_id), [
        'delivery_nama_penerima' => 'Penerima Test',
        'delivery_id_kendaraan' => $kendaraan->id,
        'delivery_id_supir' => $supir->id,
        'delivery_alamat_tujuan' => 'Jl. Tujuan',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('delivery', [
        'delivery_id_so' => $so->so_id,
        'delivery_id_kendaraan' => $kendaraan->id,
        'delivery_id_supir' => $supir->id,
        'delivery_nama_driver' => $supir->supir_nama,
        'delivery_plat_kendaraan' => $kendaraan->kendaraan_plat,
    ]);
});

it('blocks ship if invoice already exists with amount', function () {
    $customer = Customer::create(['customer_nama' => 'Test Customer', 'customer_alamat' => 'Jl. Test']);

    $so = So::create([
        'so_code' => 'SO-TEST-004',
        'so_tanggal' => now(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => 'Confirmed',
    ]);

    SoDetail::create([
        'so_detail_id_so' => $so->so_id,
        'so_detail_id_product' => $this->product->product_id,
        'so_detail_qty' => 10,
        'so_detail_harga' => 100000,
        'so_detail_code' => 'SOD-TEST-004',
    ]);

    $prepare = SoPrepare::create([
        'so_prepare_id_so' => $so->so_id,
        'so_prepare_code' => 'PREP-TEST-004',
        'so_prepare_status' => 'Done',
    ]);

    SoPrepareDetail::create([
        'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
        'so_prepare_detail_id_product' => $this->product->product_id,
        'so_prepare_detail_qty' => 5,
    ]);

    Invoice::create([
        'invoice_code' => 'INV-TEST-001',
        'invoice_tanggal' => now(),
        'invoice_id_so' => $so->so_id,
        'invoice_id_customer' => $customer->customer_id,
        'invoice_subtotal' => 100000,
        'invoice_ppn' => 11000,
        'invoice_total' => 111000,
        'invoice_status' => 'Unpaid',
    ]);

    $response = $this->post(route('wms-so.storeShip', $so->so_id), [
        'delivery_nama_penerima' => 'Penerima Test',
    ]);

    $response->assertRedirect();
});

it('blocks ship if so is not confirmed', function () {
    $customer = Customer::create(['customer_nama' => 'Test Customer', 'customer_alamat' => 'Jl. Test']);

    $so = So::create([
        'so_code' => 'SO-TEST-005',
        'so_tanggal' => now(),
        'so_id_customer' => $customer->customer_id,
        'so_status' => 'Pending',
    ]);

    $response = $this->get(route('wms-so.ship', $so->so_id));

    $response->assertRedirect();
});
