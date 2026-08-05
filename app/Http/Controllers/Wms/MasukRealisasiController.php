<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\MasukRealisasi;
use App\Models\Po;
use App\Models\Product;
use App\Models\Stock;
use App\Wms\MasukStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasukRealisasiController extends Controller
{
    use ControllerTrait;

    public function __construct(MasukRealisasi $model)
    {
        $this->model = $model::getModel();
    }

    protected function share($data = [])
    {
        return array_merge([
            'model' => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'lokasiOptions' => Lokasi::pluck('lokasi_nama', 'lokasi_code'),
            'statusOptions' => MasukRealisasi::statusOptions(),
        ], $data);
    }

    protected function getData()
    {
        return $this->model->addSelect([
            'masuk_realisasi.*',
            'in_detail_status',
        ])->leftJoin('masuk_detail', 'masuk_realisasi.in_realisasi_masuk_code', '=', 'masuk_detail.in_detail_code')
            ->with('product', 'lokasi')
            ->filter()
            ->sort();
    }

    public function getDelete(Request $request, string $id)
    {
        $realisasi = $this->model->findOrFail($id);
        $masukDetail = $realisasi->masukDetail;

        if ($masukDetail && $masukDetail->in_detail_status === MasukStatusEnum::COMPLETE) {
            flash()->error('Masuk detail sudah Complete, tidak bisa dihapus.');

            return redirect()->route('wms-masuk-realisasi.getTable');
        }

        DB::transaction(function () use ($realisasi) {
            // Hapus stock yang terkait (stock_reff = in_realisasi_group)
            if ($realisasi->in_realisasi_group) {
                Stock::where('stock_reff', $realisasi->in_realisasi_group)->delete();
            }

            $realisasi->delete();
        });

        // Update PO status setelah delete
        if ($masukDetail) {
            $this->updatePoStatus($masukDetail);
        }

        flash()->success('Masuk realisasi dan stock terkait berhasil dihapus.');

        return redirect()->route('wms-masuk-realisasi.getTable');
    }

    protected function updatePoStatus(MasukDetail $masukDetail): void
    {
        $poDetail = $masukDetail->poDetail;
        if (! $poDetail) {
            return;
        }

        $po = $poDetail->po;
        if (! $po) {
            return;
        }

        // Hitung total qty masuk detail yang sudah diproses untuk PO ini
        $poDetailCodes = $po->details()->pluck('po_detail_code')->all();
        $totalMasukQty = MasukDetail::whereIn('in_detail_reff', $poDetailCodes)
            ->whereIn('in_detail_status', [MasukStatusEnum::PROCESS, MasukStatusEnum::READY, MasukStatusEnum::COMPLETE])
            ->sum('in_detail_qty');

        $totalPoQty = $po->details()->sum('po_detail_qty');

        // Jika qty tidak sama, ubah status ke Process (hanya jika sebelumnya Done atau Ready)
        if (abs($totalMasukQty - $totalPoQty) > 0.001) {
            if ($po->po_status === Po::STATUS_DONE || $po->po_status === Po::STATUS_READY) {
                $po->update(['po_status' => Po::STATUS_PROCESS]);
            }
        }
    }
}
