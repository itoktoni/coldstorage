<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Lokasi {{ $lokasi->lokasi_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; }
        .page {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20mm;
        }
        .qr-container {
            padding: 6mm;
            border: 3px solid #111;
            border-radius: 4mm;
            display: inline-block;
        }
        .qr-container img { width: 70mm; height: 70mm; display: block; }
        .code {
            font-size: 44pt;
            font-weight: bold;
            letter-spacing: 4px;
            margin-top: 8mm;
        }
        .name {
            font-size: 18pt;
            color: #333;
            margin-top: 2mm;
        }
        .gudang {
            font-size: 14pt;
            color: #666;
            margin-top: 2mm;
        }
        .hint {
            font-size: 9pt;
            color: #999;
            margin-top: 8mm;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="qr-container">
            <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $lokasi->lokasi_code }}">
        </div>
        <div class="code">{{ $lokasi->lokasi_code }}</div>
        <div class="name">{{ $lokasi->lokasi_nama }}</div>
        @if($lokasi->gudang)
        <div class="gudang">{{ $lokasi->gudang->gudang_nama }}</div>
        @endif
        <div class="hint">Scan QR ini untuk mengidentifikasi lokasi</div>
    </div>
</body>
</html>
