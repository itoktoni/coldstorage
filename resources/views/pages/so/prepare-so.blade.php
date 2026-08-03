<?php /** @var App\Models\So $so */ ?>
<?php /** @var App\Models\SoPrepare $prepare */ ?>
<?php /** @var array $lines */ ?>
<?php /** @var \Illuminate\Support\Collection $staged_lines */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so-prepare.index'), 'label' => 'Prepare SO'], ['url' => '', 'label' => $so->so_code]]" />

    @if($errors->any())
    <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
        <ul class="list-disc list-inside text-error font-body-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- SO Info --}}
    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">point_of_sale</span>
            Sales Order - {{ $so->so_code }}
        </h3>
        <div class="grid grid-cols-12 gap-5">
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Customer</div>
                <div class="font-body-sm font-bold">{{ $so->customer->customer_nama ?? '-' }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Tanggal</div>
                <div class="font-body-sm font-bold">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</div>
            </div>
            <div class="col-span-12 md:col-span-4">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Status Prepare</div>
                <div class="font-body-sm font-bold">
                    @if($prepare->so_prepare_status === 'Done')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">Done</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">Pending</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Scan --}}
    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">qr_code_scanner</span>
            Scan Barang Staging
        </h3>
        <form action="{{ route('wms-so-prepare.update', ['soId' => $so->so_id]) }}" method="POST">
            @csrf
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Kode Stock / OUTR</label>
                    <input type="text" name="stock_scan"
                           placeholder="Scan barcode stock atau kode OUTR-..."
                           class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                </div>
                <button type="submit"
                    class="inline-flex items-center gap-2 h-11 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">check</span>
                    Scan
                </button>
            </div>
            <p class="text-xs text-on-surface-variant mt-2">Scan otomatis alokasikan sisa qty SO dari barang staging terpilih.</p>
        </form>
    </div>

    {{-- Line Status --}}
    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">checklist</span>
            Kebutuhan Item SO
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Dibutuhkan</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Teralokasi</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lines as $line)
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-medium">{{ $line['detail']->product->product_nama ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm text-right">{{ $line['qty_needed'] }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary">{{ $line['qty_assigned'] }}</td>
                        <td class="py-2 px-3 font-body-sm text-right {{ $line['qty_remaining'] <= 0 ? 'text-success' : 'text-error' }}">{{ $line['qty_remaining'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Staged Goods --}}
    <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
        <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
            <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
            Barang Staging (Hasil Picking Forklift)
        </h3>

        @if($staged_lines->isEmpty())
        <p class="text-on-surface-variant text-sm">Belum ada barang di staging untuk batch SO ini. Forklift belum melakukan picking.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant">
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">OUTR</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Stock Code</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Rak Asal</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Terpakai</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Sisa</th>
                        <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Alokasi Manual</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($staged_lines as $sl)
                    @php $r = $sl['realisasi']; @endphp
                    <tr class="border-b border-outline-variant/50">
                        <td class="py-2 px-3 font-body-sm font-mono">{{ $r->out_realisasi_code }}</td>
                        <td class="py-2 px-3 font-body-sm font-mono">{{ $sl['stock']->stock_code ?? '-' }}</td>
                        <td class="py-2 px-3 font-body-sm">{{ $sl['lokasi_nama'] }} <span class="text-on-surface-variant">({{ $sl['gudang_nama'] }})</span></td>
                        <td class="py-2 px-3 font-body-sm text-right">{{ $sl['qty_picked'] }}</td>
                        <td class="py-2 px-3 font-body-sm text-right text-primary">{{ $sl['qty_assigned'] }}</td>
                        <td class="py-2 px-3 font-body-sm text-right {{ $sl['qty_remaining'] <= 0 ? 'text-success' : 'text-on-surface-variant' }}">{{ $sl['qty_remaining'] }}</td>
                        <td class="py-2 px-3 text-right">
                            @if($sl['qty_remaining'] > 0)
                            <form action="{{ route('wms-so-prepare.update', ['soId' => $so->so_id]) }}" method="POST" class="inline-flex items-center gap-2 justify-end">
                                @csrf
                                <input type="hidden" name="assign[{{ $r->out_realisasi_id }}][realisasi_id]" value="{{ $r->out_realisasi_id }}">
                                <input type="number"
                                       name="assign[{{ $r->out_realisasi_id }}][qty]"
                                       value="{{ $sl['qty_remaining'] }}"
                                       min="0.001"
                                       max="{{ $sl['qty_remaining'] }}"
                                       step="0.001"
                                       class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                                <button type="submit"
                                    class="inline-flex items-center gap-1 h-9 px-3 text-sm font-semibold rounded-lg bg-success text-on-primary hover:bg-success/90 transition-all active:scale-95">
                                    <span class="material-symbols-outlined text-base">add_link</span>
                                    Alokasi
                                </button>
                            </form>
                            @else
                            <span class="text-xs text-success font-semibold">Terpakai semua</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="mt-6 mb-12">
        <a href="{{ route('wms-so-prepare.index') }}"
           class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
            Kembali ke Daftar Prepare
        </a>
    </div>
</x-layouts::app>