<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Lokasi {{ $lokasi->lokasi_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; background: #f5f5f5; min-height: 100vh; }

        .toolbar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; background: #fff; border-bottom: 1px solid #e0e0e0;
            position: sticky; top: 0; z-index: 10;
        }
        .toolbar button {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 600;
            border: none; cursor: pointer; transition: all 0.15s;
        }
        .btn-close { background: #e0e0e0; color: #333; }
        .btn-close:hover { background: #ccc; }
        .btn-print { background: #1e40af; color: #fff; }
        .btn-print:hover { background: #1e3a8a; }

        /* The area that will be printed */
        .print-area {
            max-width: 300px; margin: 32px auto; background: #fff;
            border-radius: 12px; border: 2px dashed #c4c5d5;
            padding: 24px 16px; text-align: center;
        }
        .print-area img { width: 120px; height: 120px; display: block; margin: 0 auto 12px; }
        .print-area .name { font-size: 16px; font-weight: bold; color: #1a1a1a; }
        .print-area .code { font-size: 12px; color: #666; margin-top: 4px; font-family: monospace; }

        /* @media print: only show the QR area, hide everything else */
        @media print {
            @page { size: 55mm 30mm; margin: 0; }
            html, body { width: 55mm; height: 30mm; background: #fff; }
            .toolbar { display: none !important; }
            .print-area {
                margin: 0; padding: 2mm; border: none; border-radius: 0;
                max-width: none; width: 55mm; height: 30mm;
                display: flex; align-items: center; gap: 2mm;
            }
            .print-area img { width: 24mm; height: 24mm; margin: 0; }
            .print-area .text-col { flex: 1; text-align: center; }
            .print-area .name { font-size: 9pt; }
            .print-area .code { font-size: 7pt; }
        }
    </style>
</head>
<body>
    {{-- Toolbar with Close + Print --}}
    <div class="toolbar">
        <button class="btn-close" onclick="goBack()">✕ Close</button>
        <button class="btn-print" onclick="printArea()">🖨 Print QR</button>
    </div>

    {{-- Print Area (only this prints) --}}
    <div class="print-area" id="print-area">
        <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $lokasi->lokasi_code }}">
        <div class="text-col">
            <div class="name">{{ $lokasi->lokasi_nama }}</div>
            <div class="code">{{ $lokasi->lokasi_code }}</div>
        </div>
    </div>

    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else if (window.NativeBridge && typeof NativeBridge.rememberExternalReturnUrl === 'function') {
                // In Android WebView, go back to the main URL
                window.location.href = '{{ url("/wms/lokasi") }}';
            } else {
                window.location.href = '{{ url("/wms/lokasi") }}';
            }
        }

        function printArea() {
            if (window.NativeBridge && typeof NativeBridge.printPage === 'function') {
                NativeBridge.printPage();
            } else {
                window.print();
            }
        }

        // Auto-print on Android WebView on first load
        @if($nativePrint ?? false)
        window.addEventListener('load', function () {
            setTimeout(function () {
                printArea();
            }, 300);
        });
        @endif
    </script>
</body>
</html>
