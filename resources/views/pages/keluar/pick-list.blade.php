<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick List - {{ $keluar->out_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #1a1a1a; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm 20mm; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 3px double #333; padding-bottom: 16px; }
        .company-info h1 { font-size: 20px; font-weight: 700; color: #0d47a1; }
        .company-info p { font-size: 11px; color: #555; line-height: 1.5; }
        .badge { text-align: right; }
        .badge h2 { font-size: 16px; font-weight: 700; color: #7b1fa2; border: 2px solid #7b1fa2; padding: 4px 16px; display: inline-block; }
        .badge p { font-size: 11px; color: #555; margin-top: 4px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .info-box { border: 1px solid #e8e8e8; border-radius: 6px; padding: 12px 16px; background: #fcfcfc; }
        .info-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; margin-bottom: 6px; font-weight: 600; }
        .info-box p { font-size: 13px; font-weight: 500; }
        .info-box .label { color: #888; font-size: 11px; font-weight: 400; }

        .section-title { font-size: 14px; font-weight: 700; color: #333; margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid #e8e8e8; display: flex; align-items: center; gap: 8px; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #7b1fa2; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        tbody tr:hover { background: #f5f5f5; }

        .pallet-card { border: 1px solid #e8e8e8; border-radius: 8px; margin-bottom: 16px; overflow: hidden; }
        .pallet-card-header { padding: 10px 16px; background: #f8f8f8; border-bottom: 1px solid #e8e8e8; font-weight: 600; font-size: 14px; }
        .pallet-table { width: 100%; border-collapse: collapse; }
        .pallet-table th { background: #fafafa; padding: 8px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; font-weight: 600; border-bottom: 1px solid #e8e8e8; }
        .pallet-table td { padding: 10px 12px; border-bottom: 1px solid #f0f0f0; }
        .pallet-table tr:last-child td { border-bottom: none; }
        .pallet-code { font-family: monospace; font-size: 12px; background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }

        .checkbox { display: inline-block; width: 16px; height: 16px; border: 2px solid #333; border-radius: 3px; vertical-align: middle; }
        .text-error { color: #ba1a1a; }
        .text-success { color: #16a34a; }

        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 50px; }
        .sig-block { text-align: center; }
        .sig-block .line { border-top: 1px solid #333; width: 180px; margin: 0 auto 6px; }
        .sig-block p { font-size: 11px; color: #555; }
        .sig-block .name { font-weight: 600; color: #1a1a1a; margin-bottom: 2px; }

        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .page { margin: 0; padding: 10mm 15mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; padding:10px; background:#f0f0f0; margin-bottom:10px;">
        <button onclick="window.print()" style="padding:8px 24px; font-size:14px; cursor:pointer; background:#7b1fa2; color:white; border:none; border-radius:6px;">
            🖨️ Cetak / Print
        </button>
        <button onclick="window.close()" style="padding:8px 24px; font-size:14px; cursor:pointer; background:#666; color:white; border:none; border-radius:6px; margin-left:8px;">
            ✕ Tutup
        </button>
    </div>

    <div class="page">
        <div class="header">
            <div class="company-info">
                <h1>COLD STORAGE INDONESIA</h1>
                <p>Jl. Pergudangan Sentra Logistik No. 88<br>Kawasan Industri Pulogadung, Jakarta Timur 13920<br>Telp: (021) 4682-5500 | Fax: (021) 4682-5501<br>Email: sales@coldstorage.co.id</p>
            </div>
            <div class="badge">
                <h2>PICK LIST</h2>
                <p>No: <strong>{{ $keluar->out_code }}</strong></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Order Info</h3>
                <p><span class="label">Tanggal:</span> {{ $keluar->out_tanggal?->format('d F Y') ?? '-' }}</p>
                <p><span class="label">Reff:</span> {{ $keluar->out_reff ?? '-' }}</p>
                <p><span class="label">Status:</span> {{ $keluar->out_status }}</p>
            </div>
            <div class="info-box">
                <h3>Summary</h3>
                <p><span class="label">Total SO:</span> {{ $soLines->count() }}</p>
                <p><span class="label">Total Qty:</span> {{ formatQty($soLines->sum('total_qty')) }}</p>
            </div>
        </div>

        <div class="section-title">
            <span class="material-symbols-outlined" style="font-size: 20px; color: #7b1fa2;">list_alt</span>
            Kebutuhan Item
        </div>
        <table>
            <thead>
                <tr>
                    <th style="width: 50px; text-align: center;">No</th>
                    <th>SO</th>
                    <th>Product</th>
                    <th style="text-align: right;">Dibutuhkan</th>
                    <th style="text-align: right;">Teralokasi</th>
                    <th style="text-align: right;">Sisa</th>
                </tr>
            </thead>
            <tbody>
                @php $no = 1; @endphp
                @foreach($soLines as $so)
                    @foreach($so['items'] as $item)
                    <tr>
                        <td style="text-align: center; color: #888;">{{ $no++ }}</td>
                        <td style="font-weight: 500;">{{ $item['so_code'] }}</td>
                        <td>{{ $item['product_nama'] }} <span style="color: #888; font-size: 11px;">{{ $item['product_kode'] }}</span></td>
                        <td style="text-align: right; font-weight: 600;">{{ formatQty($item['qty_needed']) }}</td>
                        <td style="text-align: right;">{{ formatQty($item['qty_assigned']) }}</td>
                        <td style="text-align: right; font-weight: 600; {{ $item['qty_remaining'] > 0 ? 'text-error' : 'text-success' }}">{{ formatQty($item['qty_remaining']) }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>

        <div class="section-title">
            <span class="material-symbols-outlined" style="font-size: 20px; color: #7b1fa2;">pallet</span>
            Pallet yang Diambil
        </div>
        @foreach($palletGroups as $group)
        <div class="pallet-card">
            <div class="pallet-card-header">{{ $group['product_nama'] }} <span style="font-weight: 400; color: #888; font-size: 12px;">{{ $group['product_kode'] }}</span></div>
            <table class="pallet-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;">No</th>
                        <th>Pallet</th>
                        <th>Lokasi</th>
                        <th style="width: 80px; text-align: right;">Qty</th>
                        <th style="width: 60px; text-align: center;">Done</th>
                    </tr>
                </thead>
                <tbody>
                    @if($group['assignments']->isEmpty())
                    <tr>
                        <td colspan="5" style="text-align: center; color: #999; padding: 16px;">Belum ada alokasi pallet</td>
                    </tr>
                    @else
                        @foreach($group['assignments'] as $j => $assign)
                        <tr>
                            <td style="text-align: center; color: #888;">{{ $j + 1 }}</td>
                            <td><span class="pallet-code">{{ $assign['pallet_code'] }}</span></td>
                            <td>{{ $assign['lokasi'] }}</td>
                            <td style="text-align: right; font-weight: 500;">{{ formatQty($assign['qty']) }}</td>
                            <td style="text-align: center;"><span class="checkbox"></span></td>
                        </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        @endforeach

        <div class="signatures">
            <div class="sig-block">
                <div class="line"></div>
                <p class="name">Dibuat Oleh</p>
                <p>Admin</p>
            </div>
            <div class="sig-block">
                <div class="line"></div>
                <p class="name">Diambil Oleh</p>
                <p>Warehouse Staff</p>
            </div>
        </div>

        <div class="footer">
            Pick list ini merupakan acuan pengambilan barang dari rack/staging. Centang kolom "Done" setelah item diambil.
        </div>
    </div>
</body>
</html>
