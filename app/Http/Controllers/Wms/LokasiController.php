<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Gudang;
use App\Models\Lokasi;
use Barryvdh\DomPDF\Facade\Pdf;
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
            'categoryOptions'   => Category::getOptions(),
            'gudangOptions' => Gudang::pluck('gudang_nama', 'gudang_code')
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('gudang')->filter()->sort();
    }

    public function printQrPdf(string $code)
    {
        $lokasi = $this->model->with('gudang')->where('lokasi_code', $code)->firstOrFail();
        $qrPng  = DNS2DFacade::getBarcodePNG($lokasi->lokasi_code, 'QRCODE', 8, 8);

        $pdf = Pdf::loadView('pdf.lokasi-qr', [
            'lokasi' => $lokasi,
            'qrPng'  => $qrPng,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('qr-lokasi-' . $lokasi->lokasi_code . '.pdf');
    }
}
