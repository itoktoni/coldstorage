<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_code }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; color: #1a1a1a; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 15mm 20mm; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 3px double #333; padding-bottom: 16px; }
        .company-info h1 { font-size: 20px; font-weight: 700; color: #0d47a1; }
        .company-info p { font-size: 11px; color: #555; line-height: 1.5; }
        .invoice-badge { text-align: right; }
        .invoice-badge h2 { font-size: 16px; font-weight: 700; color: #bf5b21; border: 2px solid #bf5b21; padding: 4px 16px; display: inline-block; }
        .invoice-badge p { font-size: 11px; color: #555; margin-top: 4px; }

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px; }
        .info-box { border: 1px solid #e8e8e8; border-radius: 6px; padding: 12px 16px; background: #fcfcfc; }
        .info-box h3 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; margin-bottom: 6px; font-weight: 600; }
        .info-box p { font-size: 13px; font-weight: 500; }
        .info-box .label { color: #888; font-size: 11px; font-weight: 400; }

        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        thead th { background: #bf5b21; color: white; padding: 10px 12px; text-align: left; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        thead th:nth-child(1) { width: 40px; text-align: center; }
        thead th:nth-child(4) { text-align: right; }
        thead th:nth-child(5) { text-align: right; }
        thead th:nth-child(6) { text-align: right; }
        tbody td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        tbody td:nth-child(1) { text-align: center; color: #888; }
        tbody td:nth-child(4) { text-align: right; }
        tbody td:nth-child(5) { text-align: right; font-weight: 500; }
        tbody td:nth-child(6) { text-align: right; font-weight: 600; }
        tbody tr:hover { background: #f5f5f5; }
        tfoot td { padding: 10px 12px; font-weight: 700; border-top: 2px solid #333; }
        tfoot td:last-child { text-align: right; font-size: 15px; color: #bf5b21; }

        .summary { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .summary-box { border: 1px solid #e8e8e8; border-radius: 6px; padding: 16px; background: #fdfcfa; }
        .summary-box h4 { font-size: 12px; text-transform: uppercase; color: #666; margin-bottom: 8px; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
        .summary-row.total { border-top: 2px solid #bf5b21; margin-top: 8px; padding-top: 8px; font-weight: 700; font-size: 15px; color: #bf5b21; }

        .notes { margin-bottom: 30px; padding: 12px 16px; border: 1px dashed #ddd; border-radius: 6px; background: #fefefe; }
        .notes h4 { font-size: 11px; text-transform: uppercase; color: #666; margin-bottom: 4px; }
        .notes p { font-size: 12px; color: #333; }

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
        <button onclick="window.print()" style="padding:8px 24px; font-size:14px; cursor:pointer; background:#bf5b21; color:white; border:none; border-radius:6px;">
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
            <div class="invoice-badge">
                <h2>INVOICE</h2>
                <p>No: <strong>{{ $invoice->invoice_code }}</strong></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Bill To</h3>
                <p>{{ $invoice->customer->customer_nama ?? '-' }}</p>
                @if($invoice->customer->customer_telepon)
                    <p style="font-size:11px; color:#666; margin-top:2px;">Telp: {{ $invoice->customer->customer_telepon }}</p>
                @endif
                @if($invoice->customer->customer_alamat)
                    <p style="font-size:11px; color:#666;">{{ $invoice->customer->customer_alamat }}</p>
                @endif
            </div>
            <div class="info-box">
                <h3>Invoice Details</h3>
                <p><span class="label">Tanggal:</span> {{ $invoice->invoice_tanggal->format('d F Y') }}</p>
                <p><span class="label">SO Reference:</span> {{ $invoice->so->so_code ?? '-' }}</p>
                <p><span class="label">Status:</span> {{ $invoice->invoice_status }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Product</th>
                    <th>Nama Product</th>
                    <th>Qty Kirim</th>
                    <th>Harga Satuan</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->details as $i => $detail)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $detail->product->product_kode ?? '-' }}</td>
                    <td>{{ $detail->product->product_nama ?? '-' }}</td>
                    <td>{{ number_format($detail->invoice_detail_qty, 3) }}</td>
                    <td>Rp {{ number_format($detail->invoice_detail_harga, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($detail->invoice_detail_subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Total Item: {{ $invoice->details->count() }} &nbsp;|&nbsp; Total Qty: {{ number_format($invoice->details->sum('invoice_detail_qty'), 3) }}</td>
                    <td></td>
                    <td>Rp {{ number_format($invoice->invoice_subtotal, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="summary">
            <div></div>
            <div class="summary-box">
                <h4>Ringkasan Pembayaran</h4>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>Rp {{ number_format($invoice->invoice_subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row">
                    <span>PPN (11%)</span>
                    <span>Rp {{ number_format($invoice->invoice_ppn, 0, ',', '.') }}</span>
                </div>
                <div class="summary-row total">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($invoice->invoice_total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        @if($invoice->invoice_keterangan)
        <div class="notes">
            <h4>Catatan</h4>
            <p>{{ $invoice->invoice_keterangan }}</p>
        </div>
        @endif

        <div class="signatures">
            <div class="sig-block">
                <div class="line"></div>
                <p class="name">Dibuat Oleh</p>
                <p>Admin</p>
            </div>
            <div class="sig-block">
                <div class="line"></div>
                <p class="name">Diterima Oleh</p>
                <p>Customer</p>
            </div>
        </div>

        <div class="footer">
            Invoice ini merupakan dokumen tagihan resmi. Pembayaran harap dilakukan sesuai tenggat yang berlaku.
        </div>
    </div>
</body>
</html>
