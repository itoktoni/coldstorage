<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS2DFacade;

class BarcodeController extends Controller
{
    public function generate(Request $request)
    {
        $products = Product::orderBy('product_nama')->pluck('product_nama', 'product_id');

        return view('pages.barcode.generate', [
            'products' => $products,
        ]);
    }

    public function postGenerate(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'expired_date' => ['nullable', 'date'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $products = Product::orderBy('product_nama')->pluck('product_nama', 'product_id');
        $timestamp = now()->format('YmdHis');

        $qrcodes = [];
        for ($i = 0; $i < $data['jumlah']; $i++) {
            $content = implode('#', [
                $product->product_code,
                $timestamp.strtoupper(uniqid()),
                $data['qty'],
                $data['expired_date'] ? Carbon::parse($data['expired_date'])->format('Ymd') : null,
            ]);

            $qrcodes[] = [
                'content' => $content,
                'image' => DNS2DFacade::getBarcodePNG($content, 'QRCODE', 4, 4),
            ];
        }

        return view('pages.barcode.generate', [
            'products' => $products,
            'qrcodes' => $qrcodes,
            'product' => $product,
            'qty' => $data['qty'],
            'expired' => $data['expired_date'] ?? null,
            'selectedProduct' => $data['product_id'],
            'selectedQty' => $data['qty'],
            'selectedExpired' => $data['expired_date'] ?? '',
            'selectedJumlah' => $data['jumlah'],
        ]);
    }

    public function getPdf(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'qty' => ['required', 'numeric', 'min:0.01'],
            'expired_date' => ['nullable', 'date'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $product = Product::findOrFail($request->product_id);
        $timestamp = now()->format('YmdHis');

        $qrcodes = [];
        for ($i = 0; $i < $request->jumlah; $i++) {
            $content = implode('#', [
                $product->product_code,
                $timestamp,
                $request->qty,
                $request->expired_date ? Carbon::parse($request->expired_date)->format('Ymd') : '-',
                strtoupper(uniqid()),
            ]);

            $qrcodes[] = [
                'content' => $content,
                'image' => DNS2DFacade::getBarcodePNG($content, 'QRCODE', 4, 4),
            ];
        }

        $paperWidth = 58 * 2.835;
        $paperHeight = 44 * 2.835;

        $qrHtml = '';
        foreach ($qrcodes as $index => $qr) {
            $expDisplay = $request->expired_date
                ? '<div class="info">Exp: '.Carbon::parse($request->expired_date)->format('d M Y').'</div>'
                : '';

            $qrHtml .= '<div class="qr-page">'
                .'<img src="data:image/png;base64,'.$qr['image'].'" />'
                .'<div class="name">'.e($product->product_nama).'</div>'
                .'<div class="info">Qty: '.e($request->qty).'</div>'
                .$expDisplay
                .'</div>';
        }

        $fullHtml = '<html><head><meta charset="UTF-8">'
            .'<style>'
            .'* { margin: 0; padding: 0; box-sizing: border-box; }'
            .'body { font-family: Helvetica, Arial, sans-serif; font-size: 10px; color: #333; }'
            .'.qr-page { width: '.$paperWidth.'pt; height: '.$paperHeight.'pt; text-align: center; padding: 2mm; page-break-after: always; }'
            .'.qr-page:last-child { page-break-after: auto; }'
            .'img { width: 25mm; height: 25mm; display: block; margin: 0 auto 1mm; }'
            .'.name { font-size: 7px; font-weight: bold; color: #333; margin-bottom: 1px; line-height: 1.2; }'
            .'.info { font-size: 6px; color: #666; line-height: 1.3; }'
            .'</style></head><body>'.$qrHtml.'</body></html>';

        $pdf = Pdf::loadHTML($fullHtml);
        $pdf->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait');

        if ($request->boolean('print')) {
            $printScript = <<<'HTML'
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
HTML;

            return response(str_replace('</body>', $printScript.'</body>', $fullHtml))
                ->header('Content-Type', 'text/html; charset=UTF-8');
        }

        return $pdf->download('qrcode-'.$product->product_code.'.pdf');
    }
}
