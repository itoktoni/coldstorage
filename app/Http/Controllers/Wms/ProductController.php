<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Milon\Barcode\Facades\DNS2DFacade;

class ProductController extends Controller
{
    use ControllerTrait;

    public function __construct(Product $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        $categories = Lokasi::whereNotNull('lokasi_category')
            ->distinct()
            ->pluck('lokasi_category')
            ->sort()
            ->mapWithKeys(fn ($c) => [$c => ucfirst($c)])
            ->toArray();

        return array_merge([
            'model'           => $this->model,
            'categoryOptions' => $categories,
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('stock')->filter()->sort();
    }

    public function getQrcode(Request $request, int $id)
    {
        $product = $this->model->findOrFail($id);

        return $this->views('pages.product.qrcode', [
            'model' => $product,
        ]);
    }

    public function postQrcode(Request $request, int $id)
    {
        $product = $this->model->findOrFail($id);

        $data = $request->validate([
            'qty'          => ['required', 'numeric', 'min:0.01'],
            'expired_date' => ['nullable', 'date'],
            'jumlah'       => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $qrcodes = [];
        $timestamp = now()->format('YmdHis');

        for ($i = 0; $i < $data['jumlah']; $i++) {
            $content = implode('#', [
                $product->product_code,
                $timestamp,
                $data['qty'],
                $data['expired_date'] ? Carbon::parse($data['expired_date'])->format('Ymd') : '-',
            ]);

            $qrcodes[] = [
                'content' => $content,
                'image'   => DNS2DFacade::getBarcodePNG($content, 'QRCODE', 4, 4),
            ];
        }

        return $this->views('pages.product.qrcode', [
            'model'   => $product,
            'qrcodes' => $qrcodes,
            'qty'     => $data['qty'],
            'expired' => $data['expired_date'] ?? null,
        ]);
    }
}
