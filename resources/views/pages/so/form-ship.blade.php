<?php /** @var App\Models\So $so */ ?>
<?php /** @var \Illuminate\Support\Collection $details */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so.getTable'), 'label' => 'Sales Order'], ['url' => '', 'label' => 'Kirim SO - ' . $so->so_code]]" />

    <div class="content mt-4 lg:mt-0">
        <form action="{{ route('wms-so.storeShip', ['id' => $so->so_id]) }}" method="POST">
            @csrf

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
                        <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-1">Status</div>
                        <div class="font-body-sm font-bold">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-info/10 text-info">Confirmed</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detail Qty --}}
            <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">checklist</span>
                    Detail Pengiriman
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">No</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Order</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Kirim (Real)</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Harga</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($details as $i => $d)
                            <tr class="border-b border-outline-variant/50">
                                <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="py-2 px-3 font-body-sm font-medium">{{ $d['product_nama'] }}</td>
                                <td class="py-2 px-3 font-body-sm text-right">{{ number_format($d['order_qty']) }}</td>
                                <td class="py-2 px-3 font-body-sm text-right text-primary font-bold">{{ number_format($d['real_qty'], 3) }}</td>
                                <td class="py-2 px-3 font-body-sm text-right">Rp {{ number_format($d['harga'], 0, ',', '.') }}</td>
                                <td class="py-2 px-3 font-body-sm text-right">Rp {{ number_format($d['real_qty'] * $d['harga'], 0, ',', '.') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-outline-variant">
                                <td colspan="4" class="py-2 px-3 font-body-sm font-bold">Total Qty Kirim: {{ number_format($details->sum('real_qty'), 3) }}</td>
                                <td></td>
                                <td class="py-2 px-3 font-body-sm text-right font-bold text-primary">Rp {{ number_format($details->sum(fn($d) => $d['real_qty'] * $d['harga']), 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- Delivery Details Form --}}
            <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
                <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary text-xl">local_shipping</span>
                    Data Pengiriman
                </h3>

                <div class="grid grid-cols-12 gap-5">
                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Nama Penerima</label>
                        <input type="text" name="delivery_nama_penerima" value="{{ old('delivery_nama_pembali', $so->customer->customer_nama) }}"
                               class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Nama Driver</label>
                        <input type="text" name="delivery_nama_driver" value="{{ old('delivery_nama_driver') }}"
                               class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Plat Kendaraan</label>
                        <input type="text" name="delivery_plat_kendaraan" value="{{ old('delivery_plat_kendaraan') }}"
                               class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Nama Kurir</label>
                        <input type="text" name="delivery_nama_kurir" value="{{ old('delivery_nama_kurir') }}"
                               class="w-full h-11 px-3 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                    </div>
                    <div class="col-span-12">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Alamat Tujuan</label>
                        <textarea name="delivery_alamat_tujuan" rows="3"
                                  class="w-full px-3 py-2 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">{{ old('delivery_alamat_tujuan', $so->customer->customer_alamat) }}</textarea>
                    </div>
                    <div class="col-span-12">
                        <label class="text-xs font-semibold text-on-surface-variant mb-1 block">Catatan</label>
                        <textarea name="delivery_catatan" rows="2"
                                  class="w-full px-3 py-2 bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none">{{ old('delivery_catatan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Submit --}}
            <div class="mt-6 mb-12 flex items-center gap-3">
                <a href="{{ route('wms-so.getTable') }}"
                   class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                    Batal
                </a>
                <button type="submit"
                        onclick="return confirm('Yakin ingin mengirim SO ini? Invoice & Delivery Order akan dibuat.')"
                    class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-base">local_shipping</span>
                    Kirim & Buat Invoice
                </button>
            </div>
        </form>
    </div>
</x-layouts::app>
