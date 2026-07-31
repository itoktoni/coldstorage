<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralRequest;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;

class SoController extends Controller
{
    use ControllerTrait;

    public function __construct(So $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'           => $this->model,
            'productOptions'  => Product::pluck('product_nama', 'product_id'),
            'productPrices'   => Product::pluck('product_harga', 'product_id'),
            'customerOptions' => So::customerOptions(),
            'statusOptions'   => So::statusOptions(),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with(['customer', 'details.product'])->filter()->sort();
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
        $data = $request->validate((new So)->rules());

        try {
            $so = DB::transaction(function () use ($data) {
                $so = So::create(collect($data)->except('details')->toArray());
                $this->syncDetails($so, $data['details']);

                return $so->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $so));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $data = $request->validate((new So)->rules());

        try {
            $so = DB::transaction(function () use ($data, $id) {
                $so = So::findOrFail($id);
                $so->update(collect($data)->except('details')->toArray());
                $this->syncDetails($so, $data['details']);

                return $so->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $so));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $so = So::with('details')->findOrFail($id);
                foreach ($so->details as $detail) {
                    Stock::release((int) $detail->so_detail_id_product, (int) $detail->so_detail_qty);
                }
                $so->delete();
            });

            return $this->response($this->payload(TOAST_SUCCESS, true));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    /** Sync lines, price from product master, and net stock movement. */
    private function syncDetails(So $so, array $details): void
    {
        $existing = $so->details()->get()->keyBy('so_detail_id');
        $prices = Product::whereIn('product_id', collect($details)->pluck('so_detail_id_product'))
            ->pluck('product_harga', 'product_id');

        $keepIds = [];
        $seq = 1;
        $delta = []; // product_id => net qty change (positive = extra stock to consume)

        foreach ($details as $row) {
            $productId = (int) $row['so_detail_id_product'];
            $qty = (int) $row['so_detail_qty'];
            $attrs = [
                'so_detail_id_so'      => $so->so_id,
                'so_detail_id_product' => $productId,
                'so_detail_qty'        => $qty,
                'so_detail_harga'      => $prices[$productId] ?? 0,
            ];

            $id = $row['so_detail_id'] ?? null;
            $prev = $id ? $existing->get((int) $id) : null;

            if ($prev) {
                $delta[$prev->so_detail_id_product] = ($delta[$prev->so_detail_id_product] ?? 0) - (int) $prev->so_detail_qty;
                $prev->update($attrs);
                $keepIds[] = (int) $prev->so_detail_id;
            } else {
                $attrs['so_detail_code'] = $this->nextDetailCode($so->so_code, $seq);
                $keepIds[] = (int) SoDetail::create($attrs)->so_detail_id;
            }

            $delta[$productId] = ($delta[$productId] ?? 0) + $qty;
            $seq++;
        }

        foreach ($existing as $detail) {
            if (in_array((int) $detail->so_detail_id, $keepIds, true)) {
                continue;
            }
            $delta[$detail->so_detail_id_product] = ($delta[$detail->so_detail_id_product] ?? 0) - (int) $detail->so_detail_qty;
            $detail->delete();
        }

        foreach ($delta as $productId => $qty) {
            if ($qty > 0) {
                Stock::consume((int) $productId, $qty);
            } elseif ($qty < 0) {
                Stock::release((int) $productId, -$qty);
            }
        }
    }

    private function nextDetailCode(string $soCode, int $seq): string
    {
        $code = sprintf('%s-%03d', $soCode, $seq);
        while (SoDetail::where('so_detail_code', $code)->exists()) {
            $seq++;
            $code = sprintf('%s-%03d', $soCode, $seq);
        }

        return $code;
    }
}
