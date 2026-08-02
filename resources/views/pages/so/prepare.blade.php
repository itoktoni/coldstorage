<?php /** @var array $sos */ ?>
<?php /** @var array $grouped */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so.getTable'), 'label' => 'Sales Order'], ['url' => '', 'label' => 'Prepare SO']]" />

    <form action="{{ route('wms-so.postPrepare') }}" method="POST">
        @csrf
        <input type="hidden" name="so_ids" value="{{ implode(',', $soIds) }}">

        {{-- SO Info --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">info</span>
                Sales Order Dipilih
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Kode SO</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Customer</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Tanggal</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sos as $so)
                                <tr class="border-b border-outline-variant/50">
                                    <td class="py-2 px-3 font-body-sm">{{ $so->so_code }}</td>
                                    <td class="py-2 px-3 font-body-sm">{{ $so->customer->customer_nama ?? '-' }}</td>
                                    <td class="py-2 px-3 font-body-sm">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</td>
                                    <td class="py-2 px-3 font-body-sm">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">
                                            {{ $so->so_status }}
                                        </span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Combined Items --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Item Digabung (per Product)
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">No</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Total</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Dari SO</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Adjust</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grouped as $i => $item)
                                <tr class="border-b border-outline-variant/50">
                                    <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $i + 1 }}</td>
                                    <td class="py-2 px-3 font-body-sm font-medium">{{ $item['product_nama'] }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right font-medium">{{ $item['qty'] }}</td>
                                    <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ implode(', ', $item['so_codes']) }}</td>
                                    <td class="py-2 px-3 text-right">
                                        <input type="number" name="details[{{ $i }}][qty]" value="{{ $item['qty'] }}" min="1"
                                            class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                                        <input type="hidden" name="details[{{ $i }}][product_id]" value="{{ $item['product_id'] }}" />
                                        <input type="hidden" name="details[{{ $i }}][reff]" value="{{ implode(', ', $item['so_codes']) }}" />
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-4 md:px-6 py-3 z-[45]" style="bottom: 0">
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('wms-so.getTable') }}"
                    class="inline-flex items-center justify-center gap-2 h-10 px-4 md:px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                    Batal
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95">
                    <span class="material-symbols-outlined text-xl">check</span>
                    Submit Prepare
                </button>
            </div>
        </div>
    </form>
</x-layouts::app>
