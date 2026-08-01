<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\MasukDetail;
use App\Models\Po;
use App\Models\PoDetail;
use App\Models\Product;
use App\Wms\MasukStatusEnum;
use Illuminate\Http\Request;

class PoDetailController extends Controller
{
    use ControllerTrait;

    public function __construct(PoDetail $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model'          => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'poOptions'      => Po::pluck('po_code', 'po_id'),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->with(['po', 'po.supplier' , 'product'])->filter()->sort();
    }

    public function convertToMasuk(Request $request, int $id)
    {
        $poDetail = $this->model->with('po')->findOrFail($id);

        MasukDetail::create([
            'in_detail_code'       => MasukDetail::generateCode(),
            'in_detail_reff'       => $poDetail->po_detail_code,
            'in_detail_tanggal'    => now()->toDateString(),
            'in_detail_status'     => MasukStatusEnum::PENDING,
            'in_detail_id_product' => $poDetail->po_detail_id_product,
            'in_detail_qty'        => $poDetail->po_detail_qty,
            'in_detail_catatan'    => 'Dikonversi dari PO '.$poDetail->po->po_code,
        ]);

        flash()->success('Berhasil dikonversi ke Masuk Detail!');

        return redirect()->route('wms-masuk-detail.getTable');
    }
}
