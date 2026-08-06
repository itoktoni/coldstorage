<?php /** @var App\Models\Product $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => moduleLabel()]]" />
    <div class="content mt-4 lg:mt-0">
        <x-filter :per-page="25" :fields="$fields">
            <x-slot:advanced>
                @foreach ($fields as $key => $advance)
                <x-filter-item :label="$advance" :name="$key"/>
                @endforeach
                <x-button variant="primary" class="btn-block" onclick="applyAdvanced()">Apply</x-button>
                <x-button variant="soft" class="btn-block" onclick="resetAdvanced()">Reset</x-button>
            </x-slot:advanced>
        </x-filter>

        @php
            $currentSort = request('sort.0', '');
            $sortField = str_replace(':desc','',str_replace(':asc','',$currentSort));
            $sortDir = str_contains($currentSort, ':desc') ? 'desc' : 'asc';
        @endphp

        <x-table>
            <x-slot:head>
                <x-table-checkbox :model="$model" onchange="toggleAll(this)" />
                <th>Actions</th>
                @foreach ($model::$sortColumns as $column)
                <th>{{ formatLabel($column) }}</th>
                @endforeach
                <th>Status</th>
                <th class="text-end">Qty</th>
            </x-slot:head>
            <x-slot:body>
                @foreach($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary" />
                    @foreach ($model::$sortColumns as $column)
                    <td>{{ $table->$column }}</td>
                    @endforeach
                    <td>
                        @if ($table->product_status === 'active')
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">Active</span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($table->qty, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data" />
                <div class="p-3 space-y-3" id="mBody">
                    @foreach($data as $table)
                    <div class="border border-outline-variant rounded-xl p-4 bg-surface-container-lowest shadow-sm" data-id="{{ $table->field_primary }}">
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-bold text-on-surface truncate">{{ $table->product_nama }}</p>
                                <p class="text-[10px] font-mono text-on-surface-variant mt-0.5">{{ $table->product_code }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                @if ($table->product_status === 'active')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-success/10 text-success">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">Inactive</span>
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Harga</p>
                                <p class="text-xs font-bold text-on-surface">Rp {{ number_format($table->product_harga, 0, ',', '.') }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Qty</p>
                                <p class="text-xs font-bold text-on-surface">{{ number_format($table->qty, 0, ',', '.') }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Kategori</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->product_category ?? '-' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-on-surface-variant uppercase tracking-wide mb-0.5">Exp</p>
                                <p class="text-xs font-medium text-on-surface">{{ $table->tanggal ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-outline-variant/50">
                            <span class="text-[9px] font-mono text-on-surface-variant bg-surface-container px-2 py-0.5 rounded">{{ $table->field_primary }}</span>
                            <div class="flex gap-1" onclick="event.stopPropagation()">
                                <x-table-action :model="$model" :id="$table->field_primary" />
                                <a href="{{ route('wms-product.getQrcode', $table->product_id) }}"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary/10 text-primary hover:bg-primary/20 transition-colors"
                                   title="QR Code">
                                    <span class="material-symbols-outlined text-lg">qr_code</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']"/>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
</x-layouts::app>
