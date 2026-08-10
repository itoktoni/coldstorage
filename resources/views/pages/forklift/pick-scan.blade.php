<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Forklift Pick — {{ $keluar->out_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0a;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: #1a1a2e;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3b82f6;
        }
        .header-left { display: flex; align-items: center; gap: 16px; }
        .pick-number { font-size: 24px; font-weight: 700; color: #60a5fa; }
        .keluar-code { font-size: 16px; color: #94a3b8; }

        .progress-bar {
            width: 200px;
            height: 24px;
            background: #1e293b;
            border-radius: 12px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 24px;
            gap: 20px;
        }

        .product-info {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            border: 2px solid #334155;
        }
        .product-name {
            font-size: 28px;
            font-weight: 700;
            color: #60a5fa;
            margin-bottom: 12px;
        }
        .qty-row {
            display: flex;
            gap: 32px;
            font-size: 20px;
        }
        .qty-label { color: #94a3b8; }
        .qty-value { font-weight: 700; }
        .qty-remaining { color: #f59e0b; }
        .qty-done { color: #10b981; }

        .scan-area {
            background: #0f172a;
            border: 3px solid #3b82f6;
            border-radius: 16px;
            padding: 32px;
            text-align: center;
        }
        .scan-label {
            font-size: 18px;
            color: #94a3b8;
            margin-bottom: 16px;
        }
        .scan-input {
            width: 100%;
            max-width: 500px;
            padding: 20px;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            background: #1e293b;
            border: 2px solid #475569;
            border-radius: 12px;
            color: #ffffff;
            outline: none;
            text-transform: uppercase;
        }
        .scan-input:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
        }
        .scan-input::placeholder { color: #475569; }

        .suggested {
            margin-top: 16px;
            font-size: 16px;
            color: #94a3b8;
        }
        .suggested strong { color: #10b981; }

        .history {
            background: #1e293b;
            border-radius: 16px;
            padding: 20px;
            max-height: 200px;
            overflow-y: auto;
        }
        .history-title {
            font-size: 16px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        .history-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #334155;
            font-size: 16px;
        }
        .history-icon { font-size: 20px; }
        .history-ok { color: #10b981; }
        .history-error { color: #ef4444; }

        .error-toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #dc2626;
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            display: none;
            z-index: 100;
            animation: shake 0.3s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(-50%) rotate(0); }
            25% { transform: translateX(-50%) rotate(-2deg); }
            75% { transform: translateX(-50%) rotate(2deg); }
        }

        .success-flash {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #059669;
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            display: none;
            z-index: 100;
        }

        .complete-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.9);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 24px;
            z-index: 200;
        }
        .complete-overlay.show { display: flex; }
        .complete-icon { font-size: 80px; color: #10b981; }
        .complete-text { font-size: 32px; font-weight: 700; }
        .complete-sub { font-size: 18px; color: #94a3b8; }

        #camera-reader video { width: 100%!important; height: 100%!important; object-fit: cover; border-radius: 16px; }
        #camera-reader img[alt="Info icon"] { display: none!important; }
        #camera-reader__scan_region { min-height: 100%!important; }
        #camera-reader__dashboard { display: none!important; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-left">
        <span class="pick-number">PICK {{ $summary['done_items'] + 1 }}/{{ $summary['total_items'] }}</span>
        <span class="keluar-code">{{ $keluar->out_code }}</span>
    </div>
    <div class="progress-bar">
        <div class="progress-fill" style="width: {{ $summary['progress'] }}%">
            {{ $summary['progress'] }}%
        </div>
    </div>
</div>

<div class="main">
    @if($current)
    <div class="product-info">
        <div class="product-name">{{ $current['product_nama'] }}</div>
        <div class="qty-row">
            <div>
                <span class="qty-label">Butuh: </span>
                <span class="qty-value">{{ number_format($current['qty_requested'], 0, ',', '.') }} kg</span>
            </div>
            <div>
                <span class="qty-label">Terpick: </span>
                <span class="qty-value qty-done">{{ number_format($current['qty_picked'], 0, ',', '.') }} kg</span>
            </div>
            <div>
                <span class="qty-label">Sisa: </span>
                <span class="qty-value qty-remaining">{{ number_format($current['qty_remaining'], 0, ',', '.') }} kg</span>
            </div>
        </div>
    </div>

    <div class="scan-area">
        <div class="scan-label">SCAN: Pallet / Location / Barcode</div>
        <div style="display:flex;gap:12px;align-items:stretch;justify-content:center;max-width:500px;margin:0 auto;">
            <input
                type="text"
                id="scan-input"
                class="scan-input"
                style="flex:1;max-width:none;"
                placeholder="Scan di sini..."
                autofocus
                autocomplete="off"
                data-detail-id="{{ $current['detail']->out_detail_id }}"
            >
            <button id="btn-camera" onclick="openCameraScanner()" style="min-width:64px;width:64px;background:#3b82f6;color:#fff;border:none;border-radius:12px;font-size:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(59,130,246,.3);">📷</button>
        </div>
        @if($current['suggested']->isNotEmpty())
        <div class="suggested">
            Rak suggested: <strong>{{ $current['suggested']->first()['lokasi_nama'] }}</strong>
            (FIFO, exp: {{ $current['suggested']->first()['expired'] ?? '-' }})
        </div>
        @endif
    </div>

    @if($current['detail']->out_detail_reff)
    <div style="text-align: center; color: #64748b; font-size: 14px;">
        Ref: {{ $current['detail']->out_detail_reff }}
    </div>
    @endif

    @else
    <div class="complete-overlay show">
        <div class="complete-icon">✅</div>
        <div class="complete-text">PICK SELESAI</div>
        <div class="complete-sub">{{ $keluar->out_code }} — {{ number_format($summary['total_picked'], 0, ',', '.') }} kg terpick</div>
        <div class="complete-sub" style="margin-top: 20px;">
            <a href="{{ route('wms-forklift-pick.show', ['outCode' => $keluar->out_code]) }}" style="color: #60a5fa; text-decoration: none; font-size: 18px;">
                Kembali ke pick list
            </a>
        </div>
    </div>
    @endif

    <div class="history" id="history">
        <div class="history-title">Riwayat Scan</div>
        <div id="history-list"></div>
    </div>
</div>

<div class="error-toast" id="error-toast"></div>
<div class="success-flash" id="success-flash"></div>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const pickUrl = '{{ route("wms-forklift-pick.scanProcess", ["outCode" => $keluar->out_code]) }}';
const input = document.getElementById('scan-input');
const historyList = document.getElementById('history-list');
const errorToast = document.getElementById('error-toast');
const successFlash = document.getElementById('success-flash');

input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        const code = input.value.trim();
        if (code) {
            processScan(code);
            input.value = '';
        }
    }
});

function showError(msg) {
    errorToast.textContent = msg;
    errorToast.style.display = 'block';
    setTimeout(() => { errorToast.style.display = 'none'; }, 3000);
}

function showSuccess(msg) {
    successFlash.textContent = msg;
    successFlash.style.display = 'block';
    setTimeout(() => { successFlash.style.display = 'none'; }, 2000);
}

function addHistory(code, ok, message) {
    const item = document.createElement('div');
    item.className = 'history-item';
    item.innerHTML = `
        <span class="history-icon ${ok ? 'history-ok' : 'history-error'}">${ok ? '✅' : '❌'}</span>
        <span><strong>${code}</strong> — ${message}</span>
    `;
    historyList.prepend(item);
}

async function processScan(code) {
    const detailId = input.dataset.detailId;

    try {
        const res = await fetch(pickUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ scan_code: code, detail_id: detailId }),
        });

        const data = await res.json();

        if (data.ok) {
            const pickedSummary = data.picked_items.map(i => `${i.stock_code} (${i.qty}kg)`).join(', ');
            showSuccess(`${pickedSummary} → STAGING`);
            addHistory(code, true, `${data.fulfilled}kg dipindah ke STAGING`);

            if (data.done) {
                if (data.next_detail_id) {
                    // Reload page to show next pick
                    window.location.reload();
                } else {
                    // All picks done
                    window.location.reload();
                }
            } else {
                // Update remaining display
                const remainingEl = document.querySelector('.qty-remaining');
                if (remainingEl) {
                    remainingEl.textContent = Math.max(0, data.remaining).toFixed(0) + ' kg';
                }
            }
        } else {
            showError(data.message);
            addHistory(code, false, data.message);
        }
    } catch (err) {
        showError('Terjadi kesalahan jaringan');
        addHistory(code, false, 'Network error');
    }

    input.focus();
}
</script>

<!-- Camera Scanner Modal -->
<div class="camera-overlay" id="cameraOverlay" style="display:none;position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.92);flex-direction:column;align-items:center;justify-content:flex-start;">
    <div style="width:100%;display:flex;align-items:center;justify-content:space-between;padding:16px;background:rgba(0,0,0,.7);backdrop-filter:blur(10px);-webkit-backdrop-filter:blur(10px);z-index:10;">
        <span style="color:#fff;font-size:16px;font-weight:700;">📷 Scan Kamera</span>
        <button onclick="closeCameraScanner()" style="width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
    </div>
    <div style="width:100%;flex:1;display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;">
        <div id="camera-reader" style="width:100%;max-width:420px;aspect-ratio:1/1;overflow:hidden;border-radius:16px;"></div>
        <div style="position:absolute;bottom:24px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);color:#fff;padding:10px 20px;border-radius:20px;font-size:13px;font-weight:600;white-space:nowrap;">Arahkan kamera ke barcode</div>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
let html5Qrcode = null;
let cameraScanning = false;

function openCameraScanner() {
    const overlay = document.getElementById('cameraOverlay');
    overlay.style.display = 'flex';

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
        closeCameraScanner();
        showError('Camera error: ' + err);
    });
}

function closeCameraScanner() {
    cameraScanning = false;
    if (html5Qrcode) {
        html5Qrcode.stop().catch(() => {});
        html5Qrcode.clear().catch(() => {});
        html5Qrcode = null;
    }
    document.getElementById('cameraOverlay').style.display = 'none';
    input.focus();
}
</script>

</body>
</html>
