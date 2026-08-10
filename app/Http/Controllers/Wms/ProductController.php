<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\Product;
use App\Services\SecureImageUploadService;
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
            'model' => $this->model,
            'categoryOptions' => $categories,
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('stock')->filter()->sort();
    }

    public function postCreate(Request $request)
    {
        $service = app(SecureImageUploadService::class);

        $validated = $request->validate($this->model->rules());

        if ($request->hasFile('product_image')) {
            $validated['product_image'] = $service->upload(
                $request->file('product_image'),
                'products'
            );
        } else {
            unset($validated['product_image']);
        }

        try {
            $response = $this->model->create($validated);

            return $this->response([TOAST_SUCCESS, $response]);
        } catch (\Throwable $th) {
            return $this->response([TOAST_FAILED, $th->getMessage()]);
        }
    }

    public function postUpdate(Request $request, $id)
    {
        $service = app(SecureImageUploadService::class);
        $record = $this->model->findOrFail($id);

        $validated = $request->validate($this->model->rules());

        if ($request->hasFile('product_image')) {
            if ($record->product_image) {
                $service->deleteFile($record->product_image);
            }
            $validated['product_image'] = $service->upload(
                $request->file('product_image'),
                'products'
            );
        } elseif ($request->input('remove_image') === '1') {
            if ($record->product_image) {
                $service->deleteFile($record->product_image);
            }
            $validated['product_image'] = null;
        } else {
            unset($validated['product_image']);
        }

        try {
            $record->update($validated);

            return $this->response([TOAST_SUCCESS, $record]);
        } catch (\Throwable $th) {
            return $this->response([TOAST_FAILED, $th->getMessage()]);
        }
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
            'qty' => ['required', 'numeric', 'min:0.01'],
            'expired_date' => ['nullable', 'date'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100'],
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
                'image' => DNS2DFacade::getBarcodePNG($content, 'QRCODE', 4, 4),
            ];
        }

        return $this->views('pages.product.qrcode', [
            'model' => $product,
            'qrcodes' => $qrcodes,
            'qty' => $data['qty'],
            'expired' => $data['expired_date'] ?? null,
        ]);
    }
}
