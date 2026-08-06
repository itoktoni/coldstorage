<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Stock Sales']]" />
    <div class="content mt-4 lg:mt-0">

        {{-- Stock Sales --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">storefront</span>
                Stock Available for Sales
            </h3>

            {{-- DESKTOP TABLE --}}
            <div class="hidden md:block">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant">
                                <th class="py-3 px-4 font-label-md text-on-surface-variant">Product</th>
                                <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">Warehouse</th>
                                <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">Reserved</th>
                                <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($rows as $row)
                            <tr class="border-b border-outline-variant/50 hover:bg-surface-container/50 transition-colors">
                                <td class="py-3 px-4 font-body-md text-on-surface">{{ $row['product_nama'] }}</td>
                                <td class="py-3 px-4 font-body-md text-on-surface-variant text-right">{{ number_format($row['physical'], 2, ',', '.') }}</td>
                                <td class="py-3 px-4 text-right">
                                    @if($row['reserved'] > 0)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-amber-100 text-amber-700 border border-amber-200">
                                            {{ number_format($row['reserved'], 2, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="text-on-surface-variant/40">-</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps {{ $row['available'] > 0 ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-error-container text-error border border-error/20' }}">
                                        {{ number_format($row['available'], 2, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="py-8 text-center text-on-surface-variant">Tidak ada data stock.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MOBILE CARDS --}}
            <div class="md:hidden space-y-3">
                @forelse($rows as $row)
                <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm">
                    <p class="text-sm font-bold text-on-surface truncate mb-3">{{ $row['product_nama'] }}</p>

                    <div class="grid grid-cols-3 gap-2 text-center mb-3">
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Warehouse</p>
                            <p class="text-sm font-bold text-on-surface">{{ number_format($row['physical'], 2, ',', '.') }}</p>
                        </div>
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Reserved</p>
                            @if($row['reserved'] > 0)
                                <p class="text-sm font-bold text-amber-700">{{ number_format($row['reserved'], 2, ',', '.') }}</p>
                            @else
                                <p class="text-sm font-bold text-on-surface-variant/40">-</p>
                            @endif
                        </div>
                        <div class="bg-surface-container rounded-lg p-2">
                            <p class="text-[9px] text-on-surface-variant uppercase">Available</p>
                            <p class="text-sm font-bold {{ $row['available'] > 0 ? 'text-green-700' : 'text-error' }}">{{ number_format($row['available'], 2, ',', '.') }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="text-center p-6 text-on-surface-variant">Tidak ada data stock.</div>
                @endforelse
            </div>

        </div>

    </div>
</x-layouts::app>
