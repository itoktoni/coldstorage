<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Stock Flow']]" />
    <div class="content mt-4 lg:mt-0">

        {{-- Stock Flow --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">swap_horiz</span>
                Stock Flow
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant">Product</th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-neutral"></span>
                                            Staging
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Prepare
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                            In
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-primary"></span>
                                            Reserved
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                            Road
                                        </span>
                                    </th>
                                    <th class="py-3 px-4 font-label-md text-on-surface-variant text-right">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-700"></span>
                                            Out
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rows as $row)
                                <tr class="border-b border-outline-variant/50 hover:bg-surface-container/50 transition-colors">
                                    <td class="py-3 px-4 font-body-md text-on-surface">{{ $row['product_nama'] }}</td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['staging'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-surface-container text-on-surface-variant border border-outline-variant">
                                                {{ number_format($row['staging'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['prepare'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-amber-100 text-amber-700 border border-amber-200">
                                                {{ number_format($row['prepare'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['in'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-green-100 text-green-700 border border-green-200">
                                                {{ number_format($row['in'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['reserved'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-primary-fixed text-primary border border-primary/20">
                                                {{ number_format($row['reserved'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['road'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-blue-100 text-blue-700 border border-blue-200">
                                                {{ number_format($row['road'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 text-right">
                                        @if($row['out'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full font-label-caps text-label-caps bg-green-100 text-green-800 border border-green-300">
                                                {{ number_format($row['out'], 2, ',', '.') }}
                                            </span>
                                        @else
                                            <span class="text-on-surface-variant/40">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-on-surface-variant">Tidak ada data stock.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-layouts::app>
