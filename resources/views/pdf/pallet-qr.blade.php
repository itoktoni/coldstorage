<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pallet {{ $groupCode }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #111; }
        .page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 18mm;
        }
        .qr {
            width: 72mm;
            height: 72mm;
            padding: 6mm;
            border: 3px solid #111;
            border-radius: 4mm;
        }
        .qr img { width: 100%; height: 100%; display: block; }
        .code { font-size: 26pt; font-weight: 700; margin-top: 8mm; }
        .meta { font-size: 12pt; margin-top: 2mm; }
        .small { font-size: 9pt; color: #666; margin-top: 4mm; }
        .rows { margin-top: 8mm; font-size: 9pt; width: 100%; }
        .rows table { width: 100%; border-collapse: collapse; }
        .rows th, .rows td { border: 1px solid #ddd; padding: 4px 6px; }
        .rows th { background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="page">
        <div class="qr"><img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $groupCode }}"></div>
        <div class="code">{{ $groupCode }}</div>
        <div class="meta">{{ $product->product_nama ?? '-' }}</div>
        <div class="meta">Qty Total: {{ number_format($totalQty, 3) }}</div>
        @if($detail)
        <div class="small">Ref: {{ $detail->in_detail_code }}</div>
        @endif
        <div class="rows">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Lokasi Tujuan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                    <tr>
                        <td>{{ $row->product->product_nama ?? '-' }}</td>
                        <td>{{ number_format($row->in_realisasi_qty, 3) }}</td>
                        <td>{{ $row->lokasi->lokasi_nama ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
