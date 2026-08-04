<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pallet {{ $groupCode }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; color: #111; }
        .code { font-size: 26pt; font-weight: 700; margin-top: 8mm; }
        .meta { font-size: 12pt; margin-top: 2mm; }
        .small { font-size: 9pt; color: #666; margin-top: 4mm; }
        .rows { margin-top: 8mm; font-size: 9pt; }
        .rows table { margin: 0 auto; width: 500px; border-collapse: collapse; }
        .rows th, .rows td { border: 1px solid #ddd; padding: 4px 6px; text-align: center; }
        .rows th { background: #f5f5f5; }
    </style>
</head>
<body>
    <table width="100%" cellpadding="18mm" cellspacing="0" border="0" align="center">
        <tr>
            <td align="center" valign="middle">
                 <h3 style="text-align: center;margin-top:100px">
                    <img src="data:image/png;base64,{{ $qrPng }}" alt="QR {{ $groupCode }}" style="width: 72mm; height: 72mm;">
                </h3>
                <div class="code">{{ $groupCode }}</div>
                <div class="meta">{{ $product->product_nama ?? '-' }}</div>
                <div class="meta">Qty Total: {{ number_format($totalQty, 3) }}</div>
                @if($detail)
                <div class="small">Ref: {{ $detail->in_detail_code }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
