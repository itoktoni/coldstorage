<?php /** @var array $sos */ ?>
<?php /** @var array $detailRows */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-so.getTable'), 'label' => 'Sales Order'], ['url' => '', 'label' => 'Prepare SO']]" />

    @if($errors->any())
    <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
        <ul class="list-disc list-inside text-error font-body-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('wms-so-prepare.store') }}" method="POST">
        @csrf
        @foreach($soIds as $soId)
            <input type="hidden" name="so_ids[]" value="{{ $soId }}">
        @endforeach

        {{-- SO Info --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">info</span>
                Sales Order Dipilih
            </h3>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
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

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3">
                @foreach($sos as $so)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <p class="text-sm font-bold text-on-surface truncate">{{ $so->so_code }}</p>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-warning/10 text-warning shrink-0">Prepare</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Customer</p>
                            <p class="text-xs font-medium text-on-surface truncate">{{ $so->customer->customer_nama ?? '-' }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Tanggal</p>
                            <p class="text-xs font-medium text-on-surface">{{ $so->so_tanggal?->format('d M Y') ?? '-' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Detail per SO --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Detail per SO
            </h3>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">No</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Kode SO</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Product</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                                <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty Adjust</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($detailRows as $i => $row)
                            <tr class="border-b border-outline-variant/50">
                                <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $i + 1 }}</td>
                                <td class="py-2 px-3 font-body-sm font-medium">{{ $row['so_code'] }}</td>
                                <td class="py-2 px-3 font-body-sm">{{ $row['product_nama'] }}</td>
                                <td class="py-2 px-3 font-body-sm text-right font-medium">{{ $row['qty'] }}</td>
                                <td class="py-2 px-3 text-right">
                                    <input type="number" name="details[{{ $i }}][qty]" value="{{ $row['qty'] }}" min="1"
                                        class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                                    <input type="hidden" name="details[{{ $i }}][so_detail_id]" value="{{ $row['so_detail_id'] }}" />
                                    <input type="hidden" name="details[{{ $i }}][product_id]" value="{{ $row['product_id'] }}" />
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3">
                @foreach($detailRows as $i => $row)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                    <div class="flex items-center justify-between gap-2 mb-3">
                        <p class="text-sm font-bold text-on-surface truncate">{{ $row['product_nama'] }}</p>
                        <span class="text-[10px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $row['so_code'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Qty</p>
                            <p class="text-sm font-bold text-on-surface">{{ $row['qty'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Qty Adjust</p>
                            <input type="number" name="details[{{ $i }}][qty]" value="{{ $row['qty'] }}" min="1"
                                class="w-24 h-9 px-3 text-right bg-white border border-outline-variant rounded-lg font-body-sm text-sm font-bold focus:border-primary focus:ring-1 focus:ring-primary outline-none" />
                            <input type="hidden" name="details[{{ $i }}][so_detail_id]" value="{{ $row['so_detail_id'] }}" />
                            <input type="hidden" name="details[{{ $i }}][product_id]" value="{{ $row['product_id'] }}" />
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Actions --}}
        <div class="fixed left-0 right-0 lg:left-72 bg-surface-container-lowest border-t border-outline-variant shadow-[0_-4px_12px_rgba(0,0,0,0.08)] px-3 md:px-6 py-2 md:py-3 z-[45] md:!bottom-0" style="bottom: 4rem">
            <div class="flex items-center justify-end gap-2 md:gap-3">
                <a href="{{ route('wms-so.getTable') }}"
                    class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all shrink-0">
                    <span class="material-symbols-outlined text-base md:text-xl">close</span>
                    <span class="hidden sm:inline">Batal</span>
                </a>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-1 h-8 md:h-10 px-2.5 md:px-4 text-xs md:text-sm font-semibold rounded-lg bg-primary text-on-primary hover:bg-primary/90 shadow-sm transition-all active:scale-95 shrink-0">
                    <span class="material-symbols-outlined text-base md:text-xl">check</span>
                    <span class="hidden sm:inline">Submit Prepare</span>
                </button>
            </div>
        </div>
    </form>
</x-layouts::app>
