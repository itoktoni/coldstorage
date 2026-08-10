<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Code - {{ $product->product_nama }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 55mm 30mm; margin: 0; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; }
        .qr-page {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 1.5mm;
            page-break-after: always;
            overflow: hidden;
        }
        .qr-page:last-child { page-break-after: auto; }
        .name { font-size: 7px; font-weight: bold; color: #333; margin-bottom: 1px; line-height: 1.1; }
        .info { font-size: 5.5px; color: #666; line-height: 1.2; }
    </style>
</head>
<body>
    @foreach($qrcodes as $qr)
        <div class="qr-page">
            <img style="height:60px;width:60px;margin-bottom:10px;margin-top:5px" src="data:image/png;base64,{{ $qr['image'] }}" alt="QR Code">
            <div class="name">{{ $product->product_nama }}</div>
            <div class="info">Qty: {{ $qty }}</div>
            @if($expired)
                <div class="info">Exp: {{ \Carbon\Carbon::parse($expired)->format('d M Y') }}</div>
            @endif
        </div>
    @endforeach
</body>
</html>
