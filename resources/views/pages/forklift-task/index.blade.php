<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2563eb">
<title>Forklift Worklist</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
    :root{
        --bg:#0f172a; --bg2:#1e293b;
        --surface:#ffffff; --border:#e2e8f0;
        --text:#0f172a; --muted:#64748b;
        --blue:#2563eb;  --blue-soft:#e8effe;
        --amber:#d97706; --amber-soft:#fef3c7;
        --green:#059669; --green-soft:#d1fae5;
        --red:#dc2626;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{height:100%}
    body{font-family:'Inter',system-ui,sans-serif;background:#eef2f7;color:var(--text);min-height:100vh;
        -webkit-tap-highlight-color:transparent;overflow-x:hidden;-webkit-user-select:none;user-select:none}

    /* ── HEADER ── */
    .header{position:sticky;top:0;z-index:60;
        background:linear-gradient(135deg,var(--bg),var(--bg2));color:#fff;
        padding:12px 16px;padding-top:calc(10px + env(safe-area-inset-top));
        display:flex;align-items:center;gap:12px}
    .brand{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
    .brand-icon{width:40px;height:40px;border-radius:12px;background:rgba(255,255,255,.14);
        display:flex;align-items:center;justify-content:center;font-size:22px;flex:none}
    .brand h1{font-size:16px;font-weight:900;letter-spacing:.4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.1}
    .brand .clock{font-size:12px;font-weight:700;color:#93c5fd;font-variant-numeric:tabular-nums;margin-top:2px}
    .header-right{display:flex;align-items:center;gap:10px;flex:none}
    .pill{background:rgba(255,255,255,.14);color:#fff;font-weight:700;font-size:11px;padding:6px 12px;border-radius:999px;white-space:nowrap;text-align:center;line-height:1.2}
    .pill b{font-size:18px;display:block;font-weight:900}
    .operator{display:flex;align-items:center}
    .avatar{width:36px;height:36px;border-radius:50%;background:var(--blue);color:#fff;
        display:flex;align-items:center;justify-content:center;font-weight:900;font-size:15px;flex:none;
        box-shadow:0 0 0 2px rgba(255,255,255,.25)}
    .operator span{display:none}

    /* ── BANNER ── */
    .banner{margin:14px 14px 0;border-radius:16px;padding:18px 18px;display:flex;align-items:center;gap:14px;
        border:2px solid var(--blue);background:var(--blue-soft);transition:background .25s,border-color .25s;
        box-shadow:0 6px 18px rgba(37,99,235,.12)}
    .banner .b-icon{font-size:34px;flex:none;line-height:1}
    .banner > div{min-width:0;flex:1}
    .banner .b-text{font-size:clamp(17px,5vw,24px);font-weight:900;letter-spacing:.2px;line-height:1.25;word-break:break-word}
    .banner .b-sub{font-size:13px;font-weight:600;color:var(--muted);margin-top:4px;line-height:1.35}
    .banner.go{border-color:var(--amber);background:var(--amber-soft)}
    .banner.go .b-sub{color:#92400e}
    .banner.done{border-color:var(--green);background:var(--green-soft)}
    .banner.done .b-sub{color:#065f46}
    #banner-dest{display:block;font-size:clamp(30px,10vw,30px);text-transform:uppercase;line-height:1.1;margin-top:2px;color:var(--amber)}

    /* ── SCAN BAR ── */
    .scan-area{margin:12px 14px 0;background:var(--surface);border:2px solid var(--blue);border-radius:16px;
        padding:14px;box-shadow:0 4px 16px rgba(37,99,235,.08)}
    .scan-top{display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:10px}
    .scan-pulse{width:12px;height:12px;border-radius:50%;background:var(--green);animation:pulse 1.4s infinite;flex:none}
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(5,150,105,.5)}70%{box-shadow:0 0 0 12px rgba(5,150,105,0)}100%{box-shadow:0 0 0 0 rgba(5,150,105,0)}}
    .scan-top .lbl{font-size:12px;font-weight:800;letter-spacing:1px;color:var(--green);text-transform:uppercase}
    .scan-input{width:100%;height:60px;font-size:24px;font-weight:900;letter-spacing:2px;text-align:center;text-transform:uppercase;
        border:2px solid #cbd5e1;border-radius:14px;outline:none;background:#f8fafc;color:var(--text)}
    .scan-input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.12)}
    .scan-input::placeholder{color:#cbd5e1;letter-spacing:1px;font-size:15px}
    .scan-hints{display:flex;justify-content:center;gap:18px;margin-top:10px}
    .hint{display:flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:var(--muted);white-space:nowrap}
    .hint code{background:#f1f5f9;border:1px solid var(--border);border-radius:6px;padding:2px 8px;font-weight:900;color:var(--text);font-size:12px}

    /* ── TASK LIST ── */
    .main{padding:14px 14px calc(40px + env(safe-area-inset-bottom));max-width:900px;margin:0 auto}
    .group-head{display:flex;align-items:center;gap:8px;margin:18px 2px 10px}
    .group-head:first-child{margin-top:4px}
    .group-dot{width:12px;height:12px;border-radius:4px;flex:none}
    .group-name{font-size:14px;font-weight:900;letter-spacing:1.2px}
    .group-count{margin-left:auto;flex:none;font-size:11px;font-weight:800;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:999px;padding:4px 12px}
    .rows{display:flex;flex-direction:column;gap:10px}

    /* ── TASK CARD ── */
    .trow{background:var(--surface);border:2px solid var(--border);border-left:6px solid var(--muted);
        border-radius:16px;padding:16px;transition:opacity .35s,transform .35s;
        box-shadow:0 2px 10px rgba(15,23,42,.08)}
    .trow.in-progress{background:var(--amber-soft);border-color:var(--amber);border-left-color:var(--amber)!important;animation:glow 1.6s infinite}
    @keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(217,119,6,.3)}50%{box-shadow:0 0 0 7px rgba(217,119,6,.08)}}
    .trow.done{opacity:0;transform:translateX(50px) scale(.96)}

    .card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}
    .pallet{font-size:26px;font-weight:900;letter-spacing:.3px;word-break:break-all;line-height:1.15;flex:1;min-width:0}
    .chip{font-size:11px;font-weight:900;letter-spacing:.7px;padding:6px 11px;border-radius:999px;white-space:nowrap;flex:none}
    .chip.Pending{background:#f1f5f9;color:var(--muted);border:1px solid var(--border)}
    .chip.Progress{background:var(--amber);color:#fff}

    .card-info{display:flex;gap:16px;margin-top:10px;justify-content:space-between;align-items:flex-start;
        padding:10px 12px;background:#f8fafc;border:1px solid var(--border);border-radius:12px}
    .info-item{display:flex;flex-direction:column;gap:1px;min-width:0}
    .info-item.qty{text-align:right;flex:none}
    .info-item .k{font-size:10px;font-weight:800;letter-spacing:.5px;color:var(--muted);text-transform:uppercase}
    .info-item .v{font-size:15px;font-weight:800;word-break:break-word;line-height:1.2}

    .route{display:flex;align-items:center;gap:10px;margin-top:12px;padding-top:12px;border-top:1px dashed #dbe3ec}
    .loc{display:flex;flex-direction:column;gap:1px;min-width:0;flex:1}
    .loc .lbl{font-size:10px;font-weight:900;letter-spacing:1px;color:var(--muted)}
    .loc .val{font-size:18px;font-weight:900;word-break:break-word;line-height:1.2}
    .loc.to{text-align:right}
    .loc.to .val{color:var(--green)}
    .arrow{font-size:20px;color:#94a3b8;flex:none}

    .status-bar{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:12px}
    .op{font-size:12px;font-weight:800;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:right}
    .op.has{color:var(--blue)}

    .empty{background:var(--surface);border:2px dashed #cbd5e1;border-radius:16px;text-align:center;padding:48px 16px;color:var(--muted);font-size:16px;font-weight:800;line-height:1.5}

    /* ── TABLET / DESKTOP ── */
    @media (min-width:640px){
        .header{padding:14px 22px;padding-top:calc(12px + env(safe-area-inset-top))}
        .brand-icon{width:46px;height:46px;font-size:26px}
        .brand h1{font-size:20px}
        .operator span{display:block;font-weight:800;font-size:14px;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-left:8px}
        .banner{margin:16px auto 0;max-width:calc(900px - 0px);width:calc(100% - 44px);padding:22px 24px}
        .scan-area{margin:14px auto 0;max-width:900px;width:calc(100% - 44px);padding:18px 20px}
        .scan-input{height:66px;font-size:28px}
        .main{padding:18px 22px calc(44px + env(safe-area-inset-bottom))}
        .pallet{font-size:28px}
        .loc .val{font-size:20px}
    }

    /* ── OVERLAY ── */
    .overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:100;background:rgba(15,23,42,.4);backdrop-filter:blur(3px);padding:24px}
    .overlay.show{display:flex}
    .overlay-box{width:100%;max-width:460px;text-align:center;border-radius:24px;padding:40px 28px;color:#fff;box-shadow:0 24px 60px rgba(0,0,0,.3)}
    .overlay.success .overlay-box{background:var(--green)}
    .overlay.error .overlay-box{background:var(--red);animation:shake .35s}
    .ov-icon{font-size:72px;line-height:1}
    .ov-msg{font-size:24px;font-weight:900;margin-top:16px;line-height:1.3}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-12px)}75%{transform:translateX(12px)}}

    /* ---- Camera Scanner Modal ---- */
    .camera-overlay{display:none!important;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);flex-direction:column;align-items:center;justify-content:flex-start;padding:0}
    .camera-overlay.active{display:flex!important}
    .camera-header{width:100%;display:flex;align-items:center;justify-content:space-between;padding:env(safe-area-inset-top,0px) 16px 12px;background:rgba(0,0,0,.7);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);z-index:10}
    .camera-header .title{color:#fff;font-size:16px;font-weight:700}
    .camera-close{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center}
    .camera-reader-wrap{width:100%;flex:1;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
    #camera-reader{width:100%;max-width:420px;aspect-ratio:1/1;overflow:hidden;border-radius:16px}
    #camera-reader video{width:100%!important;height:100%!important;object-fit:cover;border-radius:16px}
    #camera-reader img[alt="Info icon"]{display:none!important}
    #camera-reader__scan_region{min-height:100%!important}
    #camera-reader__dashboard{display:none!important}
    .camera-scan-hint{position:absolute;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;font-weight:600;white-space:nowrap;animation:hintPulse 2s ease-in-out infinite}
    @keyframes hintPulse{0%,100%{opacity:.8;transform:translateX(-50%) scale(1)}50%{opacity:1;transform:translateX(-50%) scale(1.03)}}
</style>
</head>
<body>
@php $operatorName = auth()->user()?->name ?? 'Operator'; @endphp

<div class="header">
    <div class="brand">
        <div class="brand-icon">🚜</div>
        <div style="min-width:0">
            <h1>FORKLIFT WORKLIST</h1>
            <div class="clock" id="clock">--:--:--</div>
        </div>
    </div>
    <div class="header-right">
        <div class="pill"><b id="task-count">{{ $tasks->count() }}</b>task</div>
        <div class="operator">
            <div class="avatar">{{ strtoupper(substr($operatorName, 0, 1)) }}</div>
            <span>{{ $operatorName }}</span>
        </div>
    </div>
</div>

<div class="banner" id="banner">
    <div class="b-icon" id="banner-icon">📦</div>
    <div>
        <div class="b-text" id="banner-text">SCAN PALLET UNTUK MULAI</div>
        <div class="b-sub" id="banner-sub">Arahkan scanner ke barcode pallet (P...)</div>
    </div>
</div>

<div class="scan-area">
    <div class="scan-top">
        <span class="scan-pulse"></span>
        <span class="lbl">Scanner siap</span>
    </div>
    <div style="display:flex;gap:10px;align-items:stretch;">
        <input type="text" id="scan-input" class="scan-input" style="flex:1;" placeholder="SCAN DI SINI…" autofocus autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="go" inputmode="none" readonly onfocus="this.removeAttribute('readonly')">
        <button id="btn-camera-scan" onclick="openCameraScanner()" style="width:64px;min-width:64px;background:var(--blue);color:#fff;border:none;border-radius:14px;font-size:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(37,99,235,.25);">📷</button>
    </div>
    <div class="scan-hints">
        <div class="hint"><code>P…</code> PALLET</div>
        <div class="hint"><code>L…</code> LOKASI / RAK</div>
        <div class="hint" onclick="openCameraScanner()" style="cursor:pointer;color:var(--blue);">📷 KAMERA</div>
    </div>
</div>

<div class="main">
    <div id="list">
        @php
            $types  = config('forklift.types', []);
            $order  = ['putaway' => 0, 'pick' => 1, 'relocation' => 2];
            $groups = $tasks->groupBy('forklift_type')
                ->sortBy(fn($rows, $type) => $order[$type] ?? 9);
        @endphp

        @forelse($groups as $type => $rows)
            @php $cfg = $types[$type] ?? ['label' => strtoupper($type), 'color' => '#64748b']; @endphp

            <div class="group-head">
                <span class="group-dot" style="background:{{ $cfg['color'] }}"></span>
                <span class="group-name" style="color:{{ $cfg['color'] }}">{{ strtoupper($type) }}</span>
                <span class="group-count">{{ $rows->count() }} task</span>
            </div>

            <div class="rows">
                @foreach($rows as $i => $task)
                <div class="trow {{ $task->forklift_status === 'Progress' ? 'in-progress' : '' }}"
                     data-task-id="{{ $task->forklift_id }}"
                     data-to="{{ $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan ?? '-' }}"
                     style="border-left-color:{{ $cfg['color'] }}">

                    <div class="card-top">
                        <div class="pallet">{{ $task->forklift_pallet_code }}</div>
                    </div>

                    @if($task->realisasiGroup->count())
                        <div class="card-info">
                            <div class="info-item">
                                <span class="k">Produk</span>
                                <span class="v">{{ $task->realisasiGroup->first()->product->product_nama ?? '-' }}</span>
                            </div>
                            <div class="info-item qty">
                                <span class="k">Qty</span>
                                <span class="v">{{ number_format($task->realisasiGroup->sum('in_realisasi_qty'), 3) }}</span>
                            </div>
                        </div>
                    @endif

                    <div class="route">
                        <div class="loc from">
                            <span class="lbl">DARI</span>
                            <span class="val">{{ $task->lokasiAsal?->lokasi_nama ?? $task->forklift_lokasi_asal ?? '-' }}</span>
                        </div>
                        <div class="arrow">➜</div>
                        <div class="loc to">
                            <span class="lbl">TUJUAN</span>
                            <span class="val">{{ $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="status-bar">
                        <span class="chip {{ $task->forklift_status }}">{{ $task->forklift_status === 'Progress' ? 'PROGRESS' : 'MENUNGGU' }}</span>
                        <span class="op {{ $task->forklift_operator ? 'has' : '' }}">{{ $task->forklift_operator ? '👤 ' . $task->forklift_operator : '—' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @empty
            <div class="empty">✅<br>Semua pekerjaan selesai<br>tidak ada task aktif.</div>
        @endforelse
    </div>
</div>

<div class="overlay" id="overlay">
    <div class="overlay-box">
        <div class="ov-icon" id="ov-icon">✅</div>
        <div class="ov-msg" id="ov-msg"></div>
    </div>
</div>

<script>
const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
const scanUrl     = '{{ route("wms-forklift-task.scan") }}';
const CURRENT_USER = @json($operatorName);

const input   = document.getElementById('scan-input');
const banner  = document.getElementById('banner');
const bIcon   = document.getElementById('banner-icon');
const bText   = document.getElementById('banner-text');
const bSub    = document.getElementById('banner-sub');
const overlay = document.getElementById('overlay');
const ovIcon  = document.getElementById('ov-icon');
const ovMsg   = document.getElementById('ov-msg');
const list    = document.getElementById('list');
const countEl = document.getElementById('task-count');

let lastTaskId  = null;
let busy        = false;
let overlayTimer = null;
let idleTimer    = null;

/* Selalu kembalikan fokus ke scan input (operator tidak menyentuh layar) */
setInterval(() => { if (document.activeElement !== input) input.focus(); }, 1500);
document.addEventListener('click', () => input.focus());

/* Jam realtime */
function tickClock() {
    document.getElementById('clock').textContent =
        new Date().toLocaleTimeString('id-ID', { hour12: false });
}
tickClock();
setInterval(tickClock, 1000);

/* Feedback suara: beep sukses / buzz error */
let actx = null;
function tone(freq, start, dur, type = 'sine') {
    const o = actx.createOscillator(), g = actx.createGain();
    o.type = type; o.frequency.value = freq;
    o.connect(g); g.connect(actx.destination);
    g.gain.setValueAtTime(.18, start);
    g.gain.exponentialRampToValueAtTime(.001, start + dur);
    o.start(start); o.stop(start + dur + .02);
}
function beep(ok) {
    try {
        actx = actx || new (window.AudioContext || window.webkitAudioContext)();
        if (actx.state === 'suspended') actx.resume();
        const t = actx.currentTime;
        if (ok) { tone(880, t, .09); tone(1320, t + .12, .12); }
        else    { tone(196, t, .25, 'square'); tone(155, t + .28, .3, 'square'); }
    } catch (e) {}
}

/* Banner panduan langkah berikutnya */
function setBanner(mode, html, sub) {
    banner.className = 'banner' + (mode ? ' ' + mode : '');
    bIcon.textContent = mode === 'go' ? '🎯' : (mode === 'done' ? '✅' : '📦');
    bText.innerHTML = html;
    bSub.textContent = sub || '';
}
function idleBanner() {
    setBanner('', 'SCAN PALLET UNTUK MULAI', 'Arahkan scanner ke barcode pallet (P...)');
}

/* Overlay fullscreen sukses / gagal */
function showOverlay(ok, msg) {
    overlay.className = 'overlay show ' + (ok ? 'success' : 'error');
    ovIcon.textContent = ok ? '✅' : '⛔';
    ovMsg.textContent = msg;
    clearTimeout(overlayTimer);
    overlayTimer = setTimeout(() => { overlay.className = 'overlay'; }, ok ? 1800 : 3200);
}

/* Helper baris task */
function findRow(id) { return list.querySelector('.trow[data-task-id="' + id + '"]'); }
function markProgress(row) {
    if (!row) return;
    row.classList.add('in-progress');
    const chip = row.querySelector('.chip');
    chip.className = 'chip Progress';
    chip.textContent = 'PROGRESS';
    const op = row.querySelector('.op');
    op.className = 'op has';
    op.textContent = '👤 ' + CURRENT_USER;
}
function markDone(row) {
    if (!row) return;
    row.classList.remove('in-progress');
    row.classList.add('done');
    setTimeout(() => { row.style.display = 'none'; }, 350);
}
function decrementCount() {
    const n = parseInt(countEl.textContent || '0', 10);
    if (n > 0) countEl.textContent = n - 1;
}
/* Scanner = keyboard wedge: Enter memicu proses */
input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        const code = input.value.trim();
        input.value = '';
        if (code) processScan(code);
    }
});

async function processScan(code) {
    if (busy) return;
    busy = true;
    try {
        const res = await fetch(scanUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ code: code }),
        });
        const data = await res.json();

        if (data.ok) {
            beep(true);
            if (data.next_scan === 'location') {
                /* Scan pallet -> kunci task, pandu ke lokasi tujuan */
                lastTaskId = data.task_id;
                const row = findRow(data.task_id);
                markProgress(row);
                const dest = row ? row.dataset.to : '';
                setBanner('go',
                    'ANTAR KE: <span id="banner-dest">' + dest + '</span>',
                    'Sekarang scan barcode lokasi / rak tujuan ');
            } else {
                /* Scan lokasi -> task selesai */
                const row = lastTaskId ? findRow(lastTaskId) : list.querySelector('.trow.in-progress');
                markDone(row);
                lastTaskId = null;
                decrementCount();
                setBanner('done', 'SELESAI ✔', 'Task berhasil diselesaikan');
                showOverlay(true, data.message || 'Task selesai');
                clearTimeout(idleTimer);
                idleTimer = setTimeout(idleBanner, 2600);
            }
        } else {
            beep(false);
            showOverlay(false, data.message || 'Scan ditolak');
        }
    } catch (err) {
        beep(false);
        showOverlay(false, 'Gangguan jaringan — silakan scan ulang');
    }
    busy = false;
    input.focus();
}

/* Auto-refresh worklist tiap 5 detik (tanpa tombol) */
setInterval(async () => {
    if (busy) return;
    try {
        const res  = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const html = await res.text();
        const doc  = new DOMParser().parseFromString(html, 'text/html');
        const newList  = doc.getElementById('list');
        const newCount = doc.getElementById('task-count');
        if (newList)  list.innerHTML = newList.innerHTML;
        if (newCount) countEl.textContent = newCount.textContent;
    } catch (e) { /* abaikan, coba lagi siklus berikutnya */ }
}, 5000);
idleBanner();
</script>

<!-- Camera Scanner Modal -->
<div class="camera-overlay" id="cameraOverlay">
    <div class="camera-header">
        <span class="title">📷 Scan Kamera</span>
        <button class="camera-close" onclick="closeCameraScanner()">✕</button>
    </div>
    <div class="camera-reader-wrap">
        <div id="camera-reader"></div>
        <div class="camera-scan-hint">Arahkan kamera ke barcode</div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5Qrcode = null;
let cameraScanning = false;

function openCameraScanner() {
    const overlay = document.getElementById('cameraOverlay');
    overlay.classList.add('active');
    overlay.classList.remove('error', 'stop');

    if (html5Qrcode) {
        html5Qrcode.clear().catch(() => {});
        html5Qrcode = null;
    }
    html5Qrcode = new Html5Qrcode('camera-reader');
    cameraScanning = true;

    html5Qrcode.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 280, height: 280 }, aspectRatio: 1.0 },
        (decodedText) => {
            if (!cameraScanning) return;
            cameraScanning = false;
            html5Qrcode.stop().catch(() => {});
            closeCameraScanner();
            processScan(decodedText);
        },
        () => {}
    ).catch(err => {
        overlay.classList.add('error');
        overlay.setAttribute('data-error', 'Camera error: ' + err);
    });
}

function closeCameraScanner() {
    cameraScanning = false;
    if (html5Qrcode) {
        html5Qrcode.stop().catch(() => {});
        html5Qrcode.clear().catch(() => {});
        html5Qrcode = null;
    }
    document.getElementById('cameraOverlay').classList.remove('active');
    input.focus();
}
</script>
</body>
</html>
