<?php /** @var App\Models\So $model */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => ucfirst(module())]]" />
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
                <x-table-sort field="so_code" label="Kode" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="customer_nama" label="Customer" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="so_tanggal" label="Tanggal" :sortField="$sortField" :sortDir="$sortDir" />
                <x-table-sort field="so_status" label="Status" :sortField="$sortField" :sortDir="$sortDir" />
            </x-slot:head>
            <x-slot:body>
                @forelse($data as $table)
                <tr>
                    <x-table-row-checkbox :model="$model" :value="$table->field_primary" />
                    <x-table-action :model="$model" :id="$table->field_primary">
                        <a href="{{ route('wms-so.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                           title="Cetak SO">
                            <span class="material-symbols-outlined text-lg">print</span>
                        </a>
                    </x-table-action>
                    <td>{{ $table->so_code }}</td>
                    <td>{{ $table->customer_nama ?? '-' }}</td>
                    <td>{{ $table->so_tanggal?->format('d M Y') ?? '-' }}</td>
                    <td>{{ $table->so_status }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="100">
                        <div class="text-center p-4">No data available.</div>
                    </td>
                </tr>
                @endforelse
            </x-slot:body>
            <x-slot:mobile>
                <x-table-mobile-select :model="$model" :total="$data" />
                <x-table-mobile-list>
                    @forelse($data as $table)
                    <x-table-mobile-item :id="$table->field_primary">
                        <x-table-mobile-header title="{{ $table->so_code }}" />
                        <x-table-mobile-text label="Tanggal" :text="$table->so_tanggal?->format('d M Y') ?? '-'" />
                        <x-table-mobile-text label="Customer" :text="$table->customer_nama ?? '-'" />
                        <x-table-mobile-text label="Status" :text="$table->so_status" />
                        <x-table-mobile-footer :label="$table->field_primary">
                            <x-table-action :model="$model" :id="$table->field_primary">
                                <a href="{{ route('wms-so.cetak', ['id' => $table->field_primary]) }}" target="_blank"
                                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-info/10 text-info hover:bg-info/20 transition-colors"
                                   title="Cetak SO">
                                    <span class="material-symbols-outlined text-lg">print</span>
                                </a>
                            </x-table-action>
                        </x-table-mobile-footer>
                    </x-table-mobile-item>
                    @empty
                    <x-table-mobile-item>
                        <div class="text-center p-4">No data available.</div>
                    </x-table-mobile-item>
                    @endforelse
                </x-table-mobile-list>
            </x-slot:mobile>
        </x-table>

        <x-pagination :paginator="$data" />
        <x-action :model="$model" :action="['create', 'delete']">
            <button type="button" onclick="prepareSelected()"
                class="inline-flex items-center justify-center gap-2 h-10 px-4 md:px-5 text-sm font-semibold rounded-lg bg-green-600 text-white hover:bg-green-700 shadow-sm transition-all active:scale-95">
                <span class="material-symbols-outlined text-xl">inventory</span>
                <span class="hidden sm:inline">Prepare</span>
            </button>
        </x-action>
    </div>

    <input type="hidden" class="module" value="{{ Str::beforeLast(request()->route()->uri(), '/') }}">
    <script src="/js/table.js"></script>
    <script>initTable('{{ $sortField }}', '{{ $sortDir }}');</script>
    <script>
    function prepareSelected() {
        const desktopIds = [...document.querySelectorAll('tbody input[type="checkbox"]:checked')].map(c => c.value);
        const ids = desktopIds.length ? desktopIds : [...mSelected];
        if (!ids.length) return alert('Pilih minimal 1 SO terlebih dahulu');
        window.location.href = '{{ route("wms-so.prepare") }}?so_ids[]=' + ids.join('&so_ids[]=');
    }
    </script>
</x-layouts::app>
