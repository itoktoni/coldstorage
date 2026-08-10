<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Gudang;
use App\Models\Lokasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS2DFacade;

class LokasiController extends Controller
{
    use ControllerTrait;

    public function __construct(Lokasi $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model' => $this->model,
            'categoryOptions' => Category::getOptions(),
            'gudangOptions' => Gudang::pluck('gudang_nama', 'gudang_code'),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('gudang')->filter()->sort();
    }

    public function printQrPdf(Request $request, string $code)
    {
        $lokasi = $this->model->with('gudang')->where('lokasi_code', $code)->firstOrFail();
        $qrPng = DNS2DFacade::getBarcodePNG($lokasi->lokasi_code, 'QRCODE', 8, 8);

        $viewData = [
            'lokasi' => $lokasi,
            'qrPng' => $qrPng,
        ];

        $userAgent = $request->userAgent() ?? '';
        $isAndroidWebView = str_contains($userAgent, 'Android') && str_contains($userAgent, '; wv');
        if ($request->boolean('render') || $isAndroidWebView) {
            return view('pages.lokasi.print-qr', $viewData);
        }

        $paperWidth = 55 * 2.835;
        $paperHeight = 30 * 2.835;

        $pdf = Pdf::loadView('pdf.lokasi-qr', $viewData)
            ->setPaper([0, 0, $paperWidth, $paperHeight], 'portrait');

        return $pdf->stream('qr-lokasi-'.$lokasi->lokasi_code.'.pdf');
    }
}
