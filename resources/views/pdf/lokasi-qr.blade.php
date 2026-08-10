<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>QR Lokasi {{ $lokasi->lokasi_code }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        @page { size: 55mm 30mm; margin: 0; }
        html, body { width: 55mm; height: 30mm; overflow: hidden; }
        body { font-family: Helvetica, Arial, sans-serif; color: #1a1a1a; }
        .page {
            display: flex;
            align-items: center;
            gap: 2mm;
            padding: 2mm;
        }
        .qr-container { flex: 0 0 24mm; }
        .qr-container img { width: 24mm; height: 24mm; display: block; }
        .name { flex: 1; font-size: 9pt; font-weight: bold; overflow-wrap: anywhere; text-align: center; }
    </style>
</head>
<body>
    <div class="page">
        <div class="qr-container">
            <h2 style="text-align: center;margin-top:5px">
                <img style="height: 60px;width:60px;margin-bottom:10px;" src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $lokasi->lokasi_code }}">
            </h2>
        </div>
        <h2 style="text-align: center;" class="name">{{ $lokasi->lokasi_nama }}</h2>
    </div>
    @if($nativePrint ?? false)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    if (window.NativeBridge && typeof NativeBridge.printPage === 'function') {
                        NativeBridge.printPage();
                    } else {
                        window.print();
                    }
                }, 250);
            });
        </script>
    @endif
</body>
</html>
