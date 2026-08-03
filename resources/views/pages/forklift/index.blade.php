<?php /** @var \Illuminate\Support\Collection $tasks */ ?>
<?php /** @var array $details */ ?>
<x-layouts::app>
    <x-breadcrumb :items="[['url' => '/dashboard', 'label' => 'Home'], ['url' => '', 'label' => 'Forklift']]" />

    <div class="content mt-4 lg:mt-0">
        @php $typeConfig = config('forklift.types'); @endphp

        @if(session('error'))
        <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
            <p class="text-error font-body-sm font-semibold">{{ session('error') }}</p>
        </div>
        @endif

        @forelse($tasks as $task)
        @php
            $t = $task['type'];
            $cfg = $typeConfig[$t] ?? ['label' => $t, 'icon' => 'local_shipping', 'color' => '#64748b'];
        @endphp
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant border-l-4 rounded-xl p-5 form-card" style="border-left-color: {{ $cfg['color'] }}">
            @if($t === 'putaway')
                @php $group = $task['group']; $groupIndex = $task['group_index']; @endphp
                <div class="pb-4 mb-4 border-b border-outline-variant flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs text-on-surface-variant uppercase tracking-widest flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm" style="color: {{ $cfg['color'] }}">{{ $cfg['icon'] }}</span>
                            {{ $cfg['label'] }}
                        </div>
                        <div class="text-xl font-bold text-on-surface break-all mt-1">{{ $group['group_code'] }}</div>
                    </div>
                    @if ($group['product_category'])
                    <span class="badge badge-warning">{{ $group['product_category'] }}</span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Produk</span>
                        <span class="text-sm font-semibold text-on-surface text-right">{{ $group['product']->product_nama ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Qty</span>
                        <span class="text-sm font-semibold text-on-surface">{{ number_format($group['total_qty'], 3) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Lokasi Tujuan Rekomendasi</span>
                        <span class="text-sm font-medium text-on-surface text-right" id="suggested-lokasi-{{ $groupIndex }}">
                            @php $suggested = $group['suitable_lokasi']->firstWhere('lokasi_code', $group['suggested_lokasi_code']); @endphp
                            @if($suggested?->lokasi_code)
                            {{ $suggested->lokasi_nama }}{{ $suggested->gudang ? ' ('.$suggested->gudang->gudang_nama.')' : '' }}
                            @else
                            -
                            @endif
                        </span>
                    </div>
                </div>

                @if(!$group['completed'])
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <div class="relative">
                        <button type="button"
                                onclick="toggleRelokasiDropdown({{ $groupIndex }})"
                                class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary/10 text-primary border border-primary/20 rounded-xl hover:bg-primary/20 transition-colors">
                            <span class="material-symbols-outlined text-lg mr-2">edit_location_alt</span>
                            ReLokasi
                        </button>
                        <div id="relokasi-dd-{{ $groupIndex }}" class="hidden absolute z-30 left-0 right-0 mt-1 bg-surface-container-lowest border border-outline-variant rounded-xl shadow-lg max-h-64 overflow-y-auto">
                            @forelse($details[$groupIndex]['suitable_lokasi'] as $lokasi)
                            <button type="button"
                                    onclick="selectRelokasi(this.dataset.group, {{ $groupIndex }}, '{{ $lokasi['lokasi_code'] }}', this.dataset.label)"
                                    data-group="{{ $groupIndex }}"
                                    data-label="{{ $lokasi['label'] }}"
                                    class="w-full text-left px-4 py-2 hover:bg-primary/10 border-b border-outline-variant last:border-b-0 transition-colors">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-medium text-on-surface">{{ $lokasi['lokasi_nama'] }}</span>
                                    <span class="text-xs font-semibold {{ is_null($lokasi['capacity_left']) ? 'text-success' : (($lokasi['capacity_left'] ?? 0) < 10 ? 'text-error' : 'text-on-surface') }}">
                                        {{ is_null($lokasi['capacity_left']) ? '∞' : number_format($lokasi['capacity_left'], 3) }}
                                    </span>
                                </div>
                                <div class="text-xs text-on-surface-variant">
                                    {{ $lokasi['gudang_nama'] ?? '-' }}
                                    @if($lokasi['lokasi_category'])
                                    &middot; <span class="badge badge-info text-xs">{{ $lokasi['lokasi_category'] }}</span>
                                    @endif
                                    &middot; <span class="font-mono">{{ number_format($lokasi['current_qty'], 3) }} / {{ is_null($lokasi['max_qty']) ? '∞' : number_format($lokasi['max_qty'], 3) }}</span>
                                </div>
                            </button>
                            @empty
                            <div class="px-4 py-3 text-sm text-on-surface-variant text-center">Tidak ada rack tersedia</div>
                            @endforelse
                        </div>
                    </div>
                    <button type="button"
                            onclick="openForkliftDetail({{ $groupIndex }})"
                            class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined text-lg mr-2">qr_code_scanner</span>
                        Scan Pallet
                    </button>
                </div>
                @else
                <div class="mt-4 p-3 bg-success/10 border border-success rounded-lg text-success text-sm">
                    Pallet ini sudah selesai dipindahkan.
                </div>
                @endif

            @else
                @php $k = $task['pick']['keluar']; $row = $task['pick']; @endphp
                <div class="pb-4 mb-4 border-b border-outline-variant flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs text-on-surface-variant uppercase tracking-widest flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm" style="color: {{ $cfg['color'] }}">{{ $cfg['icon'] }}</span>
                            {{ $cfg['label'] }}
                        </div>
                        <div class="text-xl font-bold text-on-surface break-all mt-1">{{ $k->out_code }}</div>
                    </div>
                    @if($k->out_status === 'Done')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">{{ $k->out_status }}</span>
                    @elseif($k->out_status === 'In Progress')
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">{{ $k->out_status }}</span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-surface-variant text-on-surface-variant">{{ $k->out_status }}</span>
                    @endif
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Tanggal</span>
                        <span class="text-sm font-medium text-on-surface">{{ $k->out_tanggal?->format('d M Y') ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Reff (SO)</span>
                        <span class="text-sm font-medium text-on-surface text-right break-all">{{ $k->out_reff ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Item / Qty</span>
                        <span class="text-sm font-medium text-on-surface">{{ $row['item_count'] }} item &middot; {{ $row['total_qty'] }} qty</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Progress</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 h-2 bg-outline-variant/40 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: {{ $row['progress'] }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-on-surface">{{ $row['progress'] }}%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-5">
                    <a href="{{ route('wms-forklift-pick.show', ['outCode' => $k->out_code]) }}"
                       class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
                        <span class="material-symbols-outlined text-lg mr-2">inventory_2</span>
                        Pick
                    </a>
                </div>
            @endif
        </div>
        @empty
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <div class="text-center py-8">
                <span class="material-symbols-outlined text-6xl text-on-surface-variant">check_circle</span>
                <p class="text-on-surface-variant mt-2">Tidak ada pekerjaan forklift.</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Detail / Scan Modal (one instance, filled per pallet) --}}
    <div id="forklift-detail-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md max-h-[90vh] overflow-y-auto bg-surface-container-lowest rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between px-5 py-4 border-b border-outline-variant">
                <h3 class="font-headline-md text-headline-md text-on-surface flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">qr_code_scanner</span>
                    Scan Rack
                </h3>
                <button type="button" onclick="closeForkliftDetail()" class="text-on-surface-variant hover:text-on-surface">
                    <span class="material-symbols-outlined">close</span>
                </button>
            </div>

            <form id="forklift-detail-form" action="{{ route('wms-forklift.store') }}" method="POST">
                @csrf
                <input type="hidden" name="group_code" value="">
                <input type="hidden" name="pallet_scan" value="">
                <input type="hidden" name="lokasi_code" value="">

                <div class="p-5 space-y-4">
                    <div class="bg-surface-container-low rounded-xl p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-on-surface-variant">Pallet</span>
                            <span class="text-sm font-bold text-on-surface" id="md-pallet">-</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-on-surface-variant">Produk</span>
                            <span class="text-sm font-medium text-on-surface text-right" id="md-product">-</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-on-surface-variant">Qty</span>
                            <span class="text-sm font-semibold text-on-surface" id="md-qty">-</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-on-surface-variant">Lokasi Tujuan</span>
                            <span class="text-sm font-medium text-on-surface text-right" id="md-lokasi">-</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kode Rack / Scan QR</label>
                        <input type="text" id="rack-input"
                               class="w-full border border-gray-300 rounded-lg px-3 py-3 text-lg text-center font-mono focus:ring-2 focus:ring-primary focus:border-primary"
                               placeholder="Scan / ketik kode rack lalu Enter"
                               autocomplete="off" />
                        <p class="text-xs text-on-surface-variant mt-2">Scan QR lokasi atau ketik kode rack, lalu tekan Enter.</p>
                    </div>

                    <div id="rack-result" class="hidden rounded-lg p-3 text-sm"></div>

                    <div class="flex gap-2">
                        <button type="button" onclick="closeForkliftDetail()"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                            Batal
                        </button>
                        <button type="button" id="rack-override"
                                onclick="submitForkliftDetail(true)"
                                class="px-4 py-2 bg-warning/10 text-warning border border-warning/30 rounded-lg hover:bg-warning/20 transition-colors text-sm font-medium">
                            Override
                        </button>
                        <button type="button" id="rack-confirm"
                                onclick="submitForkliftDetail(false)"
                                class="flex-1 px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary/90 transition-colors">
                            Konfirmasi
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        const detailData = @json($details);
        const overrideRelokasi = {}; // group_code -> lokasi_code pilihan user

        function toggleRelokasiDropdown(index) {
            // tutup dropdown lain
            document.querySelectorAll('[id^="relokasi-dd-"]').forEach(el => el.classList.add('hidden'));
            const dd = document.getElementById(`relokasi-dd-${index}`);
            dd.classList.toggle('hidden');
        }

        async function selectRelokasi(groupIndex, rowIndex, lokasiCode, label) {
            const d = detailData[groupIndex];
            if (!d) return;

            // tutup dropdown
            const dd = document.getElementById(`relokasi-dd-${groupIndex}`);
            if (dd) dd.classList.add('hidden');

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value
                || '';

            try {
                const fd = new FormData();
                fd.append('_token', csrf);
                fd.append('group_code', d.group_code);
                fd.append('lokasi_code', lokasiCode);

                const res = await fetch("{{ url('/wms/forklift/relokasi') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });

                const data = await res.json();

                if (!res.ok || data.ok === false) {
                    alert('Gagal update lokasi: ' + (data.message || ('HTTP ' + res.status)));
                    return;
                }

                const resolvedLabel = data.label || label;

                // update teks rekomendasi
                const target = document.getElementById(`suggested-lokasi-${groupIndex}`);
                if (target) target.textContent = resolvedLabel;

                // sinkronkan ke detailData supaya Scan Pallet pakai lokasi ini
                d.suggested = lokasiCode;
                d.lokasi = resolvedLabel;

                overrideRelokasi[d.group_code] = { lokasi_code: lokasiCode, label: resolvedLabel };

            } catch (err) {
                alert('Terjadi kesalahan: ' + err.message);
            }
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('[id^="relokasi-dd-"]') && !e.target.closest('[onclick^="toggleRelokasiDropdown"]')) {
                document.querySelectorAll('[id^="relokasi-dd-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        function openForkliftDetail(index) {
            const d = detailData[index];
            if (!d) return;

            document.getElementById('forklift-detail-form').querySelector('input[name="group_code"]').value = d.group_code;
            document.getElementById('forklift-detail-form').querySelector('input[name="pallet_scan"]').value = d.group_code;
            document.getElementById('forklift-detail-form').querySelector('input[name="lokasi_code"]').value = '';
            document.getElementById('md-pallet').textContent = d.group_code;
            document.getElementById('md-product').textContent = d.product;
            document.getElementById('md-qty').textContent = d.qty;
            document.getElementById('md-lokasi').textContent = d.lokasi;
            document.getElementById('rack-input').value = '';

            const resultEl = document.getElementById('rack-result');
            resultEl.classList.add('hidden');
            resultEl.textContent = '';

            const modal = document.getElementById('forklift-detail-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.getElementById('rack-input').focus();
        }

        function closeForkliftDetail() {
            const modal = document.getElementById('forklift-detail-modal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function showRackResult(ok, message) {
            const el = document.getElementById('rack-result');
            el.className = 'rounded-lg p-3 text-sm ' + (ok
                ? 'bg-success/10 border border-success text-success'
                : 'bg-error/10 border border-error text-error');
            el.textContent = message;
            el.classList.remove('hidden');
        }

        async function submitForkliftDetail(override = false) {
            const inputEl = document.getElementById('rack-input');
            const confirmBtn = document.getElementById('rack-confirm');
            const overrideBtn = document.getElementById('rack-override');
            const code = inputEl.value.trim();

            if (!code) {
                showRackResult(false, 'Kode rack tidak boleh kosong');
                inputEl.focus();
                return;
            }

            // Validate against suggested lokasi (skip if override)
            if (!override) {
                const form = document.getElementById('forklift-detail-form');
                const groupCode = form.querySelector('input[name="group_code"]').value;
                const d = detailData.find(dd => dd.group_code === groupCode);
                if (d && d.suggested && code !== d.suggested) {
                    showRackResult(false, 'Lokasi tidak sesuai. Scan harus ke "' + d.lokasi + '". Klik Override jika yakin.');
                    inputEl.focus();
                    inputEl.select();
                    return;
                }
            }

            const form = document.getElementById('forklift-detail-form');
            form.querySelector('input[name="lokasi_code"]').value = code;

            // Remove existing override input
            const existingOverride = form.querySelector('input[name="override"]');
            if (existingOverride) existingOverride.remove();

            if (override) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'override';
                input.value = '1';
                form.appendChild(input);
            }

            const fd = new FormData(form);

            // loading state
            inputEl.disabled = true;
            confirmBtn.disabled = true;
            overrideBtn.disabled = true;
            const originalText = confirmBtn.textContent;
            confirmBtn.textContent = 'Memproses...';
            document.getElementById('rack-result').classList.add('hidden');

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': fd.get('_token'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: fd,
                });

                const ct = res.headers.get('content-type') || '';
                const data = ct.includes('application/json') ? await res.json() : {};

                if (!res.ok || data.ok === false) {
                    // Field errors can be in `data.errors` (Laravel default) or
                    // `data.data` (custom handler in bootstrap/app.php). Collect
                    // every message from whichever shape we receive.
                    let fieldErrors = data.errors || data.data || null;
                    let detailMsg = '';
                    if (fieldErrors && typeof fieldErrors === 'object') {
                        detailMsg = Object.values(fieldErrors).flat().join(' ');
                    }
                    let msg = detailMsg || data.message;
                    if (!msg) msg = 'Gagal menyimpan pallet (HTTP ' + res.status + ')';
                    showRackResult(false, msg);

                    inputEl.disabled = false;
                    confirmBtn.disabled = false;
                    overrideBtn.disabled = false;
                    confirmBtn.textContent = originalText;
                    inputEl.focus();
                    inputEl.select();
                    return;
                }

                showRackResult(true, data.message || 'Pallet berhasil disimpan!');
                confirmBtn.textContent = 'Selesai';

                // refresh the list so the moved pallet updates/disappears
                setTimeout(() => { window.location.reload(); }, 1200);
            } catch (err) {
                showRackResult(false, 'Terjadi kesalahan: ' + err.message);
                inputEl.disabled = false;
                confirmBtn.disabled = false;
                overrideBtn.disabled = false;
                confirmBtn.textContent = originalText;
            }
        }

        document.getElementById('rack-input').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitForkliftDetail(false);
            }
        });

        document.getElementById('forklift-detail-modal').addEventListener('click', closeForkliftDetail);
    </script>
</x-layouts::app>
