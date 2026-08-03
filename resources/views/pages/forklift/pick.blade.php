<?php /** @var App\Models\Keluar $keluar */ ?>
<?php /** @var \Illuminate\Support\Collection $rows */ ?>

<x-layouts::app>
    <x-breadcrumb :items="[['url' => route('wms-forklift.index'), 'label' => 'Forklift'], ['url' => '', 'label' => $keluar->out_code]]" />

    @php
        $totalQty = $summary['total_qty'];
        $totalPicked = $summary['total_picked'];
        $overallProgress = $summary['progress'];
    @endphp

    <div class="content mt-4 lg:mt-0">
        @if(session('error'))
        <div class="bg-error/10 border border-error rounded-xl p-4 mt-5">
            <p class="text-error font-body-sm font-semibold">{{ session('error') }}</p>
        </div>
        @endif

        {{-- Pick List Info --}}
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <h3 class="font-headline-md text-headline-md text-on-surface pb-4 mb-4 border-b border-outline-variant flex items-center gap-2">
                <span class="material-symbols-outlined text-primary text-xl">inventory_2</span>
                Pick List
            </h3>
            <div class="grid grid-cols-12 gap-5">
                <div class="col-span-12 space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Kode Keluar</span>
                        <span class="text-sm font-bold text-on-surface">{{ $keluar->out_code }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Reff (SO)</span>
                        <span class="text-sm font-medium text-on-surface text-right break-all">{{ $keluar->out_reff ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Status</span>
                        <span>
                            @if($keluar->out_status === 'Done')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-success/10 text-success">{{ $keluar->out_status }}</span>
                            @elseif($keluar->out_status === 'In Progress')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-warning/10 text-warning">{{ $keluar->out_status }}</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-surface-variant text-on-surface-variant">{{ $keluar->out_status }}</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Progress</span>
                        <div class="flex items-center gap-2">
                            <div class="w-40 h-2 bg-outline-variant/40 rounded-full overflow-hidden">
                                <div class="h-full bg-primary" style="width: {{ $overallProgress }}%"></div>
                            </div>
                            <span class="text-xs font-semibold text-on-surface">{{ $overallProgress }}%</span>
                        </div>
                    </div>
                    @if($summary['done_count'] > 0)
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-sm text-on-surface-variant">Item Selesai</span>
                        <span class="text-sm font-semibold text-success">{{ $summary['done_count'] }} item</span>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Items: card per item dengan form scan rack + staging --}}
        @forelse($rows as $row)
        @php $d = $row['detail']; @endphp
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card" data-detail-id="{{ $d->out_detail_id }}">
            <div class="pb-4 mb-4 border-b border-outline-variant">
                <div class="text-xs text-on-surface-variant uppercase tracking-widest">Product</div>
                <div class="text-lg font-bold text-on-surface">{{ $d->product->product_nama ?? '-' }}</div>
                <div class="flex flex-wrap gap-4 mt-2 text-sm">
                    <span class="text-on-surface-variant">Diminta: <strong class="text-on-surface">{{ $row['qty_requested'] }}</strong></span>
                    <span class="text-on-surface-variant">Sudah diambil: <strong class="{{ $row['qty_picked'] >= $row['qty_requested'] ? 'text-success' : 'text-warning' }}">{{ $row['qty_picked'] }}</strong></span>
                    <span class="text-on-surface-variant">Sisa: <strong class="{{ $row['qty_remaining'] <= 0 ? 'text-success' : 'text-error' }}">{{ $row['qty_remaining'] }}</strong></span>
                </div>
            </div>

            @if($row['qty_remaining'] <= 0)
                <div class="bg-success/10 border border-success rounded-xl p-4">
                    <p class="text-success font-body-sm font-semibold flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg">check_circle</span>
                        Qty terpenuhi. Item selesai.
                    </p>
                </div>
            @else
                <form class="scan-pick-form" method="POST" action="{{ route('wms-forklift-pick.update', ['outCode' => $keluar->out_code]) }}">
                    @csrf
                    <input type="hidden" name="detail_id" value="{{ $d->out_detail_id }}">
                    <div class="grid grid-cols-12 gap-3">
                        <div class="col-span-12 sm:col-span-5 space-y-1">
                            <label class="text-xs font-medium text-on-surface-variant">Scan Lokasi Rack</label>
                            <input type="text" name="rack_scan"
                                   class="rack-scan w-full border border-gray-300 rounded-lg px-3 py-3 text-lg text-center font-mono focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Scan lokasi rack" autocomplete="off" />
                        </div>
                        <div class="col-span-12 sm:col-span-5 space-y-1">
                            <label class="text-xs font-medium text-on-surface-variant">Scan Rak Staging (A/B/C/D)</label>
                            <input type="text" name="staging_scan"
                                   class="staging-scan w-full border border-gray-300 rounded-lg px-3 py-3 text-lg text-center font-mono focus:ring-2 focus:ring-primary focus:border-primary"
                                   placeholder="Scan STG-A / STG-B / STG-C / STG-D" autocomplete="off" />
                        </div>
                        <div class="col-span-12 sm:col-span-2 flex items-end">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center px-4 py-3 bg-primary text-white rounded-xl hover:bg-primary/90 transition-colors">
                                <span class="material-symbols-outlined text-lg mr-2">check</span>
                                Pindah
                            </button>
                        </div>
                    </div>
                    <p class="form-feedback hidden mt-3 rounded-lg p-3 text-sm"></p>
                </form>

                @if($row['suggested']->isNotEmpty())
                <div class="mt-4">
                    <div class="text-xs text-on-surface-variant uppercase tracking-widest mb-2">{{ $row['suggested']->first()['is_assigned'] ?? false ? 'Stok yang harus diambil (Coordinator assign)' : 'Stok tersedia (FIFO — ambil dari atas)' }}</div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-outline-variant">
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">#</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Barcode</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Rak</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Gudang</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Qty</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant text-right">Bisa Diambil</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Expired</th>
                                    <th class="py-2 px-3 font-body-sm font-bold text-on-surface-variant">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($row['suggested'] as $idx => $s)
                                <tr class="border-b border-outline-variant/50 {{ ($s['is_assigned'] ?? false) ? 'bg-primary/5' : ($idx === 0 ? 'bg-primary/5' : '') }}">
                                    <td class="py-2 px-3 font-body-sm text-on-surface-variant">
                                        @if($idx === 0)
                                            <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary text-white text-xs font-bold">1</span>
                                        @else
                                            {{ $idx + 1 }}
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 font-body-sm font-mono text-sm">{{ $s['stock_code'] }}</td>
                                    <td class="py-2 px-3 font-body-sm font-medium">{{ $s['lokasi_nama'] }}</td>
                                    <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $s['gudang_nama'] }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right">{{ number_format($s['stock_qty'], 0) }}</td>
                                    <td class="py-2 px-3 font-body-sm text-right font-medium {{ $idx === 0 ? 'text-primary' : '' }}">{{ number_format($s['take_max'], 0) }}</td>
                                    <td class="py-2 px-3 font-body-sm text-on-surface-variant">{{ $s['expired'] ?? '-' }}</td>
                                    <td class="py-2 px-3 font-body-sm">
                                        @if($s['is_assigned'] ?? false)
                                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-primary">
                                                <span class="material-symbols-outlined text-sm">assignment</span>
                                                Assigned
                                            </span>
                                        @else
                                            <span class="text-xs text-on-surface-variant">FIFO</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            @endif
        </div>
        @empty
        <div class="bg-surface-container-lowest mt-5 border border-outline-variant rounded-xl p-6 form-card">
            <div class="text-center py-8">
                <span class="material-symbols-outlined text-6xl text-success">check_circle</span>
                <p class="text-success font-headline-md mt-2">Semua item selesai dipindah ke staging.</p>
            </div>
        </div>
        @endforelse

        {{-- Back --}}
        <div class="mt-6 mb-12">
            <a href="{{ route('wms-forklift.index') }}"
               class="inline-flex items-center justify-center gap-2 h-10 px-5 text-sm font-semibold rounded-lg border border-outline-variant text-on-surface-variant hover:bg-surface-container transition-all">
                Kembali ke Pick List
            </a>
        </div>
    </div>
</x-layouts::app>

<script>
document.querySelectorAll('.scan-pick-form').forEach(form => {
    const rackInput = form.querySelector('.rack-scan');
    const stagingInput = form.querySelector('.staging-scan');
    const feedback = form.querySelector('.form-feedback');

    function showFeedback(ok, message) {
        feedback.className = 'form-feedback mt-3 rounded-lg p-3 text-sm ' + (ok
            ? 'bg-success/10 border border-success text-success'
            : 'bg-error/10 border border-error text-error');
        feedback.textContent = message;
        feedback.classList.remove('hidden');
    }

    function clearFeedback() {
        feedback.classList.add('hidden');
        feedback.textContent = '';
    }

    async function submit(form) {
        const rackVal = rackInput.value.trim();
        const stagingVal = stagingInput.value.trim();

        if (!rackVal) { showFeedback(false, 'Scan lokasi rack terlebih dahulu.'); rackInput.focus(); return; }
        if (!stagingVal) { showFeedback(false, 'Scan rak staging terlebih dahulu.'); stagingInput.focus(); return; }

        clearFeedback();

        const fd = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const original = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Memproses...';

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
                let detailMsg = '';
                if (data.errors && typeof data.errors === 'object') {
                    detailMsg = Object.values(data.errors).flat().join(' ');
                }
                showFeedback(false, detailMsg || data.message || ('Gagal (HTTP ' + res.status + ')'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = original;
                return;
            }

            showFeedback(true, data.message || 'Stock dipindah ke staging.');
            setTimeout(() => { window.location.reload(); }, 1000);
        } catch (err) {
            showFeedback(false, 'Terjadi kesalahan: ' + err.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = original;
        }
    }

    stagingInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            submit(form);
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        submit(form);
    });
});
</script>
