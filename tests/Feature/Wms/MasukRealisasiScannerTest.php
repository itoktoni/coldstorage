<?php

use App\Livewire\MasukRealisasiScanner;
use App\Models\Gudang;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Product;
use App\Models\User;
use App\Wms\MasukStatusEnum;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::create([
        'name'     => 'Scanner Tester',
        'email'    => 'scanner-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
        'role'     => 'admin',
    ]));

    $gudang = Gudang::create(['gudang_code' => 'GD-'.uniqid(), 'gudang_nama' => 'WH-'.uniqid()]);
    $this->lokasi = Lokasi::create([
        'lokasi_code'        => 'LOC-'.uniqid(),
        'lokasi_nama'        => 'Rak-'.uniqid(),
        'lokasi_code_gudang' => $gudang->gudang_code,
    ]);
    $this->product = Product::create([
        'product_nama'   => 'Susu UHT 1L',
        'product_code'   => 'PROD-17',
        'product_harga'  => 10,
        'product_kategori' => 'frozen',
    ]);

    $this->detail = MasukDetail::create([
        'in_detail_code'       => 'IN-'.now()->format('Ymd').'-'.uniqid(),
        'in_detail_tanggal'    => '2026-01-01',
        'in_detail_status'     => MasukStatusEnum::PENDING,
        'in_detail_id_product' => $this->product->product_id,
        'in_detail_qty'        => 100,
    ]);

    // realisasi auto-created oleh postConvertSingleRow
    $this->realisasi = MasukRealisasi::create([
        'in_realisasi_masuk_code'   => $this->detail->in_detail_code,
        'in_realisasi_code'         => 'INR-'.uniqid(),
        'in_realisasi_id_product'   => $this->product->product_id,
        'in_realisasi_qty'          => 100,
        'in_realisasi_code_lokasi'  => $this->lokasi->lokasi_code,
        'in_realisasi_barcode'      => 'PROD-17#20260803000107#100#-',
    ]);
});

it('renders auto-created realisasi in summary after convert', function () {
    Livewire::test(MasukRealisasiScanner::class, ['masukDetailId' => $this->detail->in_detail_code])
        ->assertSee('Summary Realisasi')
        ->assertSee($this->product->product_nama)
        ->assertSee('100');
});

it('populates detail modal with the auto-created realisasi', function () {
    $component = Livewire::test(MasukRealisasiScanner::class, ['masukDetailId' => $this->detail->in_detail_code]);

    $component->call('getDetail', $this->product->product_id);

    expect($component->get('selectedProductId'))->toBe($this->product->product_id);
    expect($component->get('scans'))->toHaveCount(1);
    expect($component->get('scans')[0]->in_realisasi_barcode)->toBe('PROD-17#20260803000107#100#-');
    $component->assertSee('PROD-17#20260803000107#100#-');
});

it('keeps summary qty after scanning a second barcode', function () {
    MasukRealisasi::create([
        'in_realisasi_masuk_code'   => $this->detail->in_detail_code,
        'in_realisasi_code'         => 'INR-'.uniqid(),
        'in_realisasi_id_product'   => $this->product->product_id,
        'in_realisasi_qty'          => 100,
        'in_realisasi_code_lokasi'  => $this->lokasi->lokasi_code,
        'in_realisasi_barcode'      => 'PROD-17#20260803000109#100#-',
    ]);

    $component = Livewire::test(MasukRealisasiScanner::class, ['masukDetailId' => $this->detail->in_detail_code]);

    expect((float) $component->get('summary')->sum('total_qty'))->toBe(200.0);
});

it('inserts realisasi into stock with group reff when detail becomes ready', function () {
    $component = Livewire::test(MasukRealisasiScanner::class, ['masukDetailId' => $this->detail->in_detail_code]);

    $component->call('changeStatus', MasukStatusEnum::PROCESS->value);
    $component->call('changeStatus', MasukStatusEnum::READY->value);

    $group = $this->realisasi->refresh()->in_realisasi_group;
    expect($group)->not->toBeNull();

    $stock = Stock::where('stock_reff', $group)->get();
    expect($stock)->toHaveCount(1);
    expect($stock->first()->stock_id_product)->toBe($this->product->product_id);
    expect((float) $stock->first()->stock_qty)->toBe(100.0);
    expect($stock->first()->stock_code_lokasi)->toBeNull();
    expect($stock->first()->stock_type)->toBe(\App\Models\Stock::TYPE_STAGING);
});
