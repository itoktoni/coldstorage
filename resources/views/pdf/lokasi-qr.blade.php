<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Lokasi {{ $lokasi->lokasi_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 55mm 30mm; margin: 0; }
        body { width: 55mm; height: 30mm; font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; }
        .page {
            width: 55mm;
            display: flex;
            align-items: center;
            gap: 2mm;
            padding: 2mm;
        }
        .qr-container { flex: 0 0 24mm; }
        .qr-container img { width: 24mm; height: 24mm; display: block; }
    </style>
</head>
<body>
    <div class="page" style="width:100%;margin-left:-10px">
        <h2 style="text-align:center">
        <div class="qr-container" style="margin:0px auto">
            <img style="height: 70px;width:70px" src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $lokasi->lokasi_code }}">
        </div>
        <div class="name" style="font-size:12px;margin-top:5px">{{ $lokasi->lokasi_nama }}</div>
        </h2>
    </div>
</body>
</html>
