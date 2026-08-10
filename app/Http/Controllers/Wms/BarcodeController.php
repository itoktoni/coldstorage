<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        return view('pages.barcode.pdf', [
            'product' => $product,
            'qrcodes' => $qrcodes,
            'qty' => $request->qty,
            'expired' => $request->expired_date,
            'nativePrint' => $request->boolean('print'),
        ]);
    }
}
