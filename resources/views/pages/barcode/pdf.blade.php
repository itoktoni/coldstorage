<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Code - {{ $product->product_nama }}</title>
    <style>
        @page { size: 58mm 44mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 58mm; height: 44mm; overflow: hidden; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 10px; color: #333; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 2mm; }
        img { width: 20mm; height: 20mm; display: block; margin: 0 auto 1mm; }
        .name { font-size: 7px; font-weight: bold; color: #333; margin-bottom: 1px; line-height: 1.2; }
        .info { font-size: 6px; color: #666; line-height: 1.3; }
    </style>
</head>
<body>
    <img style="height: 30px;width:30px" src="data:image/png;base64,{{ $qrcodes[0]['image'] }}" alt="QR Code" />
    <div class="name">{{ $product->product_nama }}</div>
    <div class="info">Qty: {{ (float) $qty }}</div>
    @if($expired)
    <div class="info">Exp: {{ \Carbon\Carbon::parse($expired)->format('d M Y') }}</div>
    @endif
</body>
</html>
