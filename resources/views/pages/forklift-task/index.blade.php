<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="theme-color" content="#2563eb">
<title>Forklift Worklist</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root{
        --bg:#eef2f7; --surface:#ffffff; --border:#dbe3ec;
        --text:#0f172a; --muted:#64748b;
        --blue:#2563eb;  --blue-soft:#e8effe;
        --amber:#d97706; --amber-soft:#fef3c7;
        --green:#059669; --green-soft:#d1fae5;
        --red:#dc2626;
    }
    *{margin:0;padding:0;box-sizing:border-box}
    html,body{height:100%}
    body{font-family:'Inter',system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;
        -webkit-tap-highlight-color:transparent;overflow-x:hidden}

    /* ── HEADER (mobile: 1 baris, subtitle/clock/name hidden) ── */
    .header{position:sticky;top:0;z-index:60;background:var(--surface);border-bottom:1px solid var(--border);
        box-shadow:0 1px 8px rgba(15,23,42,.05);
        padding:8px 12px;padding-top:calc(8px + env(safe-area-inset-top));
        display:flex;align-items:center;gap:8px;flex-wrap:nowrap}
    .brand{display:flex;align-items:center;gap:8px;min-width:0;flex:1}
    .brand-icon{width:30px;height:30px;border-radius:8px;background:var(--blue-soft);color:var(--blue);
        display:flex;align-items:center;justify-content:center;font-size:17px;flex:none}
    .brand h1{font-size:13px;font-weight:800;letter-spacing:.3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .brand p{display:none}
    .header-right{display:flex;align-items:center;gap:6px;flex:none}
    .clock{display:none}
    .pill{background:var(--blue-soft);color:var(--blue);font-weight:700;font-size:11px;padding:4px 10px;border-radius:999px;white-space:nowrap}
    .pill b{font-size:13px}
    .operator{display:flex;align-items:center}
    .avatar{width:28px;height:28px;border-radius:50%;background:var(--blue);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;flex:none}
    .operator span{display:none}

    /* ── BANNER ── */
    .banner{margin:10px 12px 0;border-radius:11px;padding:11px 13px;display:flex;align-items:center;gap:10px;
        border:2px solid var(--blue);background:var(--blue-soft);transition:background .25s,border-color .25s}
    .banner .b-icon{font-size:22px;flex:none}
    .banner > div{min-width:0}
    .banner .b-text{font-size:clamp(14px,4.2vw,20px);font-weight:800;letter-spacing:.2px;line-height:1.25;word-break:break-word}
    .banner .b-sub{font-size:11px;font-weight:600;color:var(--muted);margin-top:2px}
    .banner.go{border-color:var(--amber);background:var(--amber-soft)}
    .banner.go .b-sub{color:#92400e}
    .banner.done{border-color:var(--green);background:var(--green-soft)}
    .banner.done .b-sub{color:#065f46}
    #banner-dest{font-size:clamp(19px,5.5vw,28px);text-transform:uppercase}

    /* ── SCAN BAR ── */
    .scan-area{margin:10px 12px 0;background:var(--surface);border:2px solid var(--blue);border-radius:11px;
        padding:11px;box-shadow:0 4px 16px rgba(37,99,235,.07);
        display:grid;grid-template-columns:auto 1fr;grid-template-areas:"pulse input" "hints hints";
        gap:7px 10px;align-items:center}
    .scan-pulse{grid-area:pulse;width:12px;height:12px;border-radius:50%;background:var(--green);animation:pulse 1.4s infinite;justify-self:center}
    @keyframes pulse{0%{box-shadow:0 0 0 0 rgba(5,150,105,.5)}70%{box-shadow:0 0 0 12px rgba(5,150,105,0)}100%{box-shadow:0 0 0 0 rgba(5,150,105,0)}}
    .scan-hints{grid-area:hints;display:flex;justify-content:center;gap:16px}
    .hint{display:flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:var(--muted);white-space:nowrap}
    .hint code{background:#f1f5f9;border:1px solid var(--border);border-radius:5px;padding:1px 6px;font-weight:800;color:var(--text)}
    .scan-input{grid-area:input;width:100%;min-width:0;height:54px;font-size:18px;font-weight:800;letter-spacing:1.5px;text-align:center;text-transform:uppercase;
        border:2px solid #cbd5e1;border-radius:10px;outline:none;background:#f8fafc;color:var(--text)}
    .scan-input:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.12)}
    .scan-input::placeholder{color:#cbd5e1;letter-spacing:1px;font-size:13px}

    /* ── TASK LIST ── */
    .main{padding:12px 12px calc(32px + env(safe-area-inset-bottom));max-width:1200px;margin:0 auto}
    .group-head{display:flex;align-items:center;gap:7px;margin:14px 2px 6px;flex-wrap:nowrap}
    .group-head:first-child{margin-top:0}
    .group-dot{width:10px;height:10px;border-radius:3px;flex:none}
    .group-name{font-size:12px;font-weight:800;letter-spacing:1px}
    .group-label{display:none}
    .group-count{margin-left:auto;flex:none;font-size:10px;font-weight:700;color:var(--muted);background:var(--surface);border:1px solid var(--border);border-radius:999px;padding:2px 8px}
    .rows{display:flex;flex-direction:column;gap:6px}

    /* ── TASK CARD MOBILE: 2 area, no num, no op ── */
    .trow{display:grid;grid-template-columns:1fr auto;grid-template-areas:"main status" "route route";
        align-items:start;
        background:var(--surface);border:1px solid var(--border);border-left:4px solid var(--muted);
        border-radius:10px;padding:10px 11px;transition:opacity .3s,transform .3s}
    .trow.in-progress{background:var(--amber-soft);border-color:var(--amber);border-left-color:var(--amber);animation:glow 1.6s infinite}
    @keyframes glow{0%,100%{box-shadow:0 0 0 0 rgba(217,119,6,.3)}50%{box-shadow:0 0 0 6px rgba(217,119,6,.08)}}
    .trow.done{opacity:0;transform:translateX(40px) scale(.97)}
    .num{display:none}
    .main-cell{grid-area:main;display:flex;flex-direction:column;gap:2px;min-width:0}
    .pallet{font-size:16px;font-weight:800;letter-spacing:.3px;word-break:break-all;line-height:1.2}
    .badge{display:inline-flex;font-size:9px;font-weight:800;letter-spacing:.6px;text-transform:uppercase;padding:2px 6px;border-radius:4px;vertical-align:middle}
    .reff{font-size:10px;font-weight:600;color:var(--muted);word-break:break-word}
    .route{grid-area:route;display:flex;align-items:center;gap:6px;padding:7px 0 0;margin-top:4px;border-top:1px solid #edf1f6}
    .loc{display:flex;flex-direction:column;gap:0;min-width:0}
    .loc .lbl{font-size:8px;font-weight:800;letter-spacing:1px;color:var(--muted);line-height:1.4}
    .loc .val{font-size:13px;font-weight:800;word-break:break-word;line-height:1.2}
    .loc.to .val{color:var(--green)}
    .arrow{font-size:13px;color:#94a3b8;flex:none}
    .status-cell{grid-area:status;display:flex;align-items:flex-start;justify-content:flex-end;padding-top:1px}
    .chip{font-size:9px;font-weight:800;letter-spacing:.7px;padding:3px 9px;border-radius:999px;white-space:nowrap}
    .chip.Pending{background:#f1f5f9;color:var(--muted);border:1px solid var(--border)}
    .chip.Progress{background:var(--amber);color:#fff}
    .op{display:none}
    .empty{background:var(--surface);border:2px dashed #cbd5e1;border-radius:12px;text-align:center;padding:32px 14px;color:var(--muted);font-size:13px;font-weight:700}

    /* ── TABLET >=640px ── */
    @media (min-width:640px){
        .header{padding:10px 18px;padding-top:calc(10px + env(safe-area-inset-top));gap:10px}
        .brand{flex:none;gap:10px}
        .brand-icon{width:40px;height:40px;font-size:24px;border-radius:10px}
        .brand h1{font-size:16px}
        .brand p{display:block;font-size:11px;color:var(--muted);font-weight:600;white-space:nowrap}
        .header-right{margin-left:auto;gap:10px}
        .clock{display:block;font-variant-numeric:tabular-nums;font-weight:800;font-size:15px;background:#f8fafc;border:1px solid var(--border);padding:6px 11px;border-radius:9px}
        .pill{font-size:12px;padding:6px 12px}
        .pill b{font-size:15px}
        .operator{gap:7px;background:#f8fafc;border:1px solid var(--border);padding:4px 11px 4px 4px;border-radius:999px}
        .operator span{display:block;font-weight:700;font-size:12px;max-width:140px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .avatar{width:30px;height:30px;font-size:13px}
        .banner{margin:14px 18px 0;padding:14px 16px;gap:12px;border-radius:13px}
        .banner .b-icon{font-size:28px}
        .banner .b-sub{font-size:12px}
        .scan-area{margin:14px 18px 0;padding:13px 16px;
            grid-template-columns:auto auto 1fr;grid-template-areas:"pulse hints input";gap:14px;border-radius:13px}
        .scan-hints{flex-direction:column;justify-content:center;gap:4px}
        .hint{font-size:11px}
        .scan-input{height:58px;font-size:22px}
        .main{padding:16px 18px calc(32px + env(safe-area-inset-bottom))}
        .group-head{gap:8px;margin:16px 2px 8px}
        .group-dot{width:12px;height:12px;border-radius:4px}
        .group-name{font-size:13px;letter-spacing:1.2px}
        .group-label{display:inline;font-size:12px;font-weight:600;color:var(--muted)}
        .group-count{font-size:11px;padding:3px 11px}
        .rows{gap:8px}
        .trow{grid-template-columns:auto 1fr auto;grid-template-areas:"num main status" "num route route";
            padding:11px 13px;border-left-width:5px;border-radius:11px}
        .trow:hover{box-shadow:0 2px 10px rgba(15,23,42,.06)}
        .num{display:block;grid-area:num;font-size:14px;font-weight:800;color:#cbd5e1;text-align:center;padding-top:2px;min-width:18px}
        .main-cell{gap:3px}
        .pallet{font-size:18px}
        .badge{font-size:9px;padding:2px 7px}
        .reff{font-size:11px}
        .route{gap:8px;padding:6px 8px 0;margin-top:4px;border-top:1px solid #edf1f6}
        .loc .lbl{font-size:9px}
        .loc .val{font-size:15px}
        .arrow{font-size:14px}
        .chip{font-size:10px;padding:4px 10px}
        .op{display:inline;font-size:10px;font-weight:700;color:var(--muted);max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
        .op.has{color:var(--blue)}
        .empty{font-size:15px;padding:40px 16px}
    }

    /* ── DESKTOP >=900px ── */
    @media (min-width:900px){
        .header{padding:14px 24px;gap:16px}
        .brand-icon{width:48px;height:48px;font-size:28px;border-radius:12px}
        .brand h1{font-size:22px}
        .brand p{font-size:13px}
        .header-right{gap:12px}
        .clock{font-size:20px;padding:8px 14px;border-radius:12px}
        .pill{font-size:14px;padding:8px 16px}
        .pill b{font-size:18px}
        .operator{padding:6px 14px 6px 6px;gap:10px}
        .avatar{width:34px;height:34px;font-size:16px}
        .operator span{font-size:14px;max-width:none}
        .banner{margin:18px auto 0;padding:18px 24px;gap:16px;border-radius:16px;max-width:calc(1200px - 48px)}
        .banner .b-icon{font-size:36px}
        .banner .b-sub{font-size:14px}
        .scan-area{margin:16px auto 0;padding:18px 24px;gap:20px;border-radius:16px;max-width:calc(1200px - 48px)}
        .scan-hints{min-width:160px}
        .hint{font-size:13px}
        .scan-input{height:66px;font-size:30px;letter-spacing:2px}
        .scan-input::placeholder{letter-spacing:3px;font-size:30px}
        .main{padding:20px 24px 40px}
        .group-head{gap:10px;margin:20px 4px 10px}
        .group-dot{width:14px;height:14px}
        .group-name{font-size:16px;letter-spacing:1.5px}
        .group-label{font-size:14px}
        .group-count{font-size:13px;padding:3px 14px}
        .rows{gap:10px}
        .trow{grid-template-columns:40px minmax(200px,1fr) 1.5fr 170px;
            grid-template-areas:"num main route status";
            align-items:center;gap:16px;padding:14px 18px;border-left-width:6px;border-radius:12px}
        .trow:hover{box-shadow:0 3px 12px rgba(15,23,42,.07)}
        .num{font-size:20px;padding-top:0}
        .main-cell{gap:5px}
        .pallet{font-size:23px}
        .badge{font-size:11px;padding:3px 10px}
        .reff{font-size:12px}
        .route{gap:16px;background:transparent;border:none;padding:0;margin-top:0}
        .loc .lbl{font-size:11px}
        .loc .val{font-size:19px}
        .arrow{font-size:22px}
        .status-cell{flex-direction:column;align-items:flex-end;gap:6px}
        .chip{font-size:12px;padding:5px 14px}
        .op{font-size:12px;max-width:none}
        .empty{padding:56px 20px;font-size:19px}
    }

    /* ── OVERLAY ── */
    .overlay{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:100;background:rgba(15,23,42,.28);backdrop-filter:blur(2px);padding:24px}
    .overlay.show{display:flex}
    .overlay-box{width:100%;max-width:520px;text-align:center;border-radius:20px;padding:28px 24px;color:#fff;box-shadow:0 20px 50px rgba(0,0,0,.25)}
    .overlay.success .overlay-box{background:var(--green)}
    .overlay.error .overlay-box{background:var(--red);animation:shake .35s}
    .ov-icon{font-size:52px;line-height:1}
    .ov-msg{font-size:20px;font-weight:800;margin-top:12px}
    @keyframes shake{0%,100%{transform:translateX(0)}25%{transform:translateX(-10px)}75%{transform:translateX(10px)}}
    @media (min-width:640px){
        .overlay-box{padding:34px 44px}
        .ov-icon{font-size:60px}
        .ov-msg{font-size:24px}
    }
</style>
</head>
<body>
@php $operatorName = auth()->user()?->name ?? 'Operator'; @endphp

<div class="header">
    <div class="brand">
        <div class="brand-icon">🚜</div>
        <div>
            <h1>FORKLIFT WORKLIST</h1>
            <p>Scan saja — tidak perlu menyentuh layar</p>
        </div>
    </div>
    <div class="header-right">
        <div class="clock" id="clock">--:--:--</div>
        <div class="pill"><b id="task-count">{{ $tasks->count() }}</b>&nbsp;task aktif</div>
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
    <div class="scan-pulse"></div>
    <div class="scan-hints">
        <div class="hint"><code>P…</code> = PALLET</div>
        <div class="hint"><code>L…</code> = LOKASI / RAK</div>
    </div>
    <input type="text" id="scan-input" class="scan-input" placeholder="SCAN DI SINI…" autofocus autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="go">
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
                <span class="group-label">{{ $cfg['label'] }}</span>
                <span class="group-count">{{ $rows->count() }} task</span>
            </div>

            <div class="rows">
                @foreach($rows as $i => $task)
                <div class="trow {{ $task->forklift_status === 'Progress' ? 'in-progress' : '' }}"
                     data-task-id="{{ $task->forklift_id }}"
                     data-to="{{ $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan ?? '-' }}"
                     style="border-left-color:{{ $cfg['color'] }}">

                    <div class="num">{{ $i + 1 }}</div>

                    <div class="main-cell">
                        <div class="pallet">{{ $task->forklift_pallet_code }}</div>
                        <div>
                            <span class="badge" style="background:{{ $cfg['color'] }}1a;color:{{ $cfg['color'] }}">{{ $type }}</span>
                            @if($task->forklift_reff)
                                <span class="reff">&nbsp;·&nbsp;{{ $task->forklift_reff }}</span>
                            @endif
                        </div>
                    </div>

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

                    <div class="status-cell">
                        <span class="chip {{ $task->forklift_status }}">{{ $task->forklift_status === 'Progress' ? 'PROGRESS' : 'MENUNGGU' }}</span>
                        <span class="op {{ $task->forklift_operator ? 'has' : '' }}">{{ $task->forklift_operator ? '👤 ' . $task->forklift_operator : '—' }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        @empty
            <div class="empty">✅ &nbsp;Semua pekerjaan selesai — tidak ada task aktif.</div>
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
</body>
</html>
