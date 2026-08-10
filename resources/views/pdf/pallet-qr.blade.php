<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pallet {{ $groupCode }}</title>
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

        .print-area {
            max-width: 400px; margin: 32px auto; background: #fff;
            border-radius: 12px; border: 2px dashed #c4c5d5;
            padding: 24px 16px; text-align: center;
        }
        .print-area img { width: 160px; height: 160px; display: block; margin: 0 auto 12px; }
        .print-area .code { font-size: 22px; font-weight: 700; color: #1a1a1a; margin-top: 8px; }
        .print-area .meta { font-size: 14px; color: #444; margin-top: 4px; }
        .print-area .small { font-size: 11px; color: #888; margin-top: 4px; }

        @media print {
            @page { size: 72mm 72mm; margin: 0; }
            html, body { width: 72mm; height: 72mm; background: #fff; }
            .toolbar { display: none !important; }
            .print-area {
                margin: 0; padding: 4mm; border: none; border-radius: 0;
                max-width: none; width: 72mm; height: 72mm;
                display: flex; flex-direction: column; align-items: center; justify-content: center;
            }
            .print-area img { width: 50mm; height: 50mm; margin: 0 auto 3mm; }
            .print-area .code { font-size: 18pt; }
            .print-area .meta { font-size: 10pt; }
            .print-area .small { font-size: 8pt; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button class="btn-close" onclick="goBack()">✕ Close</button>
        <button class="btn-print" onclick="printArea()">🖨 Print QR</button>
    </div>

    <div class="print-area" id="print-area">
        <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $groupCode }}">
        <div class="code">{{ $groupCode }}</div>
        <div class="meta">{{ $product->product_nama ?? '-' }}</div>
        <div class="meta">Qty Total: {{ number_format($totalQty, 3) }}</div>
        @if($detail)
        <div class="small">Ref: {{ $detail->in_detail_code }}</div>
        @endif
    </div>

    <script>
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '{{ url("/wms/forklift") }}';
            }
        }

        function printArea() {
            if (window.NativeBridge && typeof NativeBridge.printPage === 'function') {
                NativeBridge.printPage();
            } else {
                window.print();
            }
        }

        @if($nativePrint ?? false)
        window.addEventListener('load', function () {
            setTimeout(function () { printArea(); }, 300);
        });
        @endif
    </script>
</body>
</html>
