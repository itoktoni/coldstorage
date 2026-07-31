<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralRequest;
use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class PoController extends Controller
{
    use ControllerTrait;

    public function __construct(Po $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'           => $this->model,
            'productOptions'  => Product::pluck('product_nama', 'product_id'),
            'supplierOptions' => Po::supplierOptions(),
            'statusOptions'   => Po::statusOptions(),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with('details.product')->filter()->sort();
    }

    public function getUpdate(GeneralRequest $request, $id)
    {
        $data = $this->model->with('details')->findOrFail($id);

        return $this->views($this->template(), [
            'model' => $data,
        ]);
    }

    public function postCreate(GeneralRequest $request)
    {
        $data = $request->validate((new Po)->rules());

        try {
            $po = DB::transaction(function () use ($data) {
                $po = Po::create(collect($data)->except('details')->toArray());
                $this->syncDetails($po, $data['details']);

                return $po->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $po));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $data = $request->validate((new Po)->rules());

        try {
            $po = DB::transaction(function () use ($data, $id) {
                $po = Po::findOrFail($id);
                $po->update(collect($data)->except('details')->toArray());
                $this->syncDetails($po, $data['details']);

                return $po->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $po));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    private function syncDetails(Po $po, array $details): void
    {
        $existingIds = $po->details()->pluck('po_detail_id')->all();
        $keepIds = [];
        $seq = 1;

        foreach ($details as $row) {
            $attrs = [
                'po_detail_id_po'      => $po->po_id,
                'po_detail_id_product' => (int) $row['po_detail_id_product'],
                'po_detail_qty'        => (int) $row['po_detail_qty'],
            ];

            $id = $row['po_detail_id'] ?? null;
            if ($id && in_array((int) $id, $existingIds, true)) {
                $detail = PoDetail::find($id);
                $detail->update($attrs);
                $keepIds[] = (int) $detail->po_detail_id;
            } else {
                $attrs['po_detail_code'] = $this->nextDetailCode($po->po_code, $seq);
                $detail = PoDetail::create($attrs);
                $keepIds[] = (int) $detail->po_detail_id;
            }
            $seq++;
        }

        foreach (array_diff($existingIds, $keepIds) as $removeId) {
            PoDetail::where('po_detail_id', $removeId)->delete();
        }
    }

    private function nextDetailCode(string $poCode, int $seq): string
    {
        $code = sprintf('%s-%03d', $poCode, $seq);
        // ensure unique if collision
        while (PoDetail::where('po_detail_code', $code)->exists()) {
            $seq++;
            $code = sprintf('%s-%03d', $poCode, $seq);
        }

        return $code;
    }
}
