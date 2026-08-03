<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralRequest;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\StockAssignment;
use App\Wms\SoStatusEnum;
use App\Models\Stock;
use Illuminate\Http\Request;
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
        $physicalStock = DB::table('stock')
            ->select('stock_id_product', DB::raw('SUM(stock_qty) as total'))
            ->where('stock_type', 'IN')
            ->groupBy('stock_id_product')
            ->pluck('total', 'stock_id_product');

        $reserved = DB::table('detail_so')
            ->select('so_detail_id_product', DB::raw('SUM(so_detail_qty) as total'))
            ->groupBy('so_detail_id_product')
            ->pluck('total', 'so_detail_id_product');

        $availableStock = $physicalStock->map(function ($qty, $id) use ($reserved) {
            return max(0, $qty - ($reserved->get($id, 0)));
        });

        return array_merge([
            'model'           => $this->model,
            'productOptions'  => Product::pluck('product_nama', 'product_id'),
            'productPrices'   => Product::pluck('product_harga', 'product_id'),
            'customerOptions' => So::customerOptions(),
            'statusOptions'   => So::statusOptions(),
            'availableStock'  => $availableStock,
        ], $data);
    }

    protected function getData()
    {
        return $this->model->addSelect([
            'so.*',
            'customer_nama',
        ])->leftJoinRelationship('customer')->filter()->sort();
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
            $this->validateAvailableStock($data['details']);

            $so = DB::transaction(function () use ($data) {
                $so = So::create(collect($data)->except('details')->toArray());
                $this->syncDetails($so, $data['details']);

                return $so->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $so));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    public function postUpdate(GeneralRequest $request, $id)
    {
        $data = $request->validate((new So)->rules());

        try {
            $this->validateAvailableStock($data['details'], (int) $id);

            $so = DB::transaction(function () use ($data, $id) {
                $so = So::findOrFail($id);
                $so->update(collect($data)->except('details')->toArray());
                $this->syncDetails($so, $data['details']);

                return $so->load('details.product');
            });

            return $this->response($this->payload(TOAST_SUCCESS, $so));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    public function getDelete(GeneralRequest $request, $id)
    {
        try {
            DB::transaction(function () use ($id) {
                $so = So::with('details')->findOrFail($id);
                Stock::where('stock_type', Stock::TYPE_RESERVE)
                    ->where('stock_reff', $so->so_code)
                    ->delete();
                $so->delete();
            });

            return $this->response($this->payload(TOAST_SUCCESS, true));
        } catch (\Throwable $th) {
            return $this->response($this->payload(TOAST_FAILED, $th->getMessage()));
        }
    }

    /** Sync lines, price from product master, and RESERVE stock rows. */
    private function syncDetails(So $so, array $details): void
    {
        $existing = $so->details()->get()->keyBy('so_detail_id');
        $prices = Product::whereIn('product_id', collect($details)->pluck('so_detail_id_product'))
            ->pluck('product_harga', 'product_id');

        $keepIds = [];
        $seq = 1;

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
                $prev->update($attrs);
                $keepIds[] = (int) $prev->so_detail_id;
            } else {
                $attrs['so_detail_code'] = $this->nextDetailCode($so->so_code, $seq);
                $keepIds[] = (int) SoDetail::create($attrs)->so_detail_id;
            }

            $seq++;
        }

        foreach ($existing as $detail) {
            if (in_array((int) $detail->so_detail_id, $keepIds, true)) {
                continue;
            }
            $detail->delete();
        }

        $this->syncReserve($so, $details);
    }

    /** Rebuild RESERVE stock rows for an SO (aggregated per product, reff = so_code). */
    private function syncReserve(So $so, array $details): void
    {
        Stock::where('stock_type', Stock::TYPE_RESERVE)
            ->where('stock_reff', $so->so_code)
            ->delete();

        $grouped = collect($details)->groupBy('so_detail_id_product')
            ->map(fn ($rows) => $rows->sum('so_detail_qty'));

        foreach ($grouped as $productId => $qty) {
            if ($qty <= 0) {
                continue;
            }
            Stock::create([
                'stock_id_product'  => (int) $productId,
                'stock_qty'         => (float) $qty,
                'stock_type'        => Stock::TYPE_RESERVE,
                'stock_reff'        => $so->so_code,
                'stock_code_lokasi' => null,
            ]);
        }
    }

    private function validateAvailableStock(array $details, ?int $excludeSoId = null): void
    {
        $physicalStock = DB::table('stock')
            ->select('stock_id_product', DB::raw('SUM(stock_qty) as total'))
            ->where('stock_type', 'IN')
            ->groupBy('stock_id_product')
            ->pluck('total', 'stock_id_product');

        $reserved = DB::table('detail_so')
            ->select('so_detail_id_product', DB::raw('SUM(so_detail_qty) as total'))
            ->when($excludeSoId, fn ($q) => $q->where('so_detail_id_so', '!=', $excludeSoId))
            ->groupBy('so_detail_id_product')
            ->pluck('total', 'so_detail_id_product');

        $grouped = collect($details)->groupBy('so_detail_id_product')
            ->map(fn ($rows) => $rows->sum('so_detail_qty'));

        foreach ($grouped as $productId => $qty) {
            $stock = (float) ($physicalStock[$productId] ?? 0);
            $reservedQty = (float) ($reserved[$productId] ?? 0);
            $available = $stock - $reservedQty;

            if ($qty > $available) {
                $name = Product::where('product_id', $productId)->value('product_nama') ?? $productId;
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'details' => "Stok product \"{$name}\" tidak mencukupi. Tersedia: {$available}, diminta: {$qty}.",
                ]);
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

    public function getPrepare(Request $request)
    {
        $soIds = $request->query('so_ids', []);
        if (empty($soIds)) {
            return redirect()->route('wms-so.getTable')->with('error', 'Pilih minimal 1 SO terlebih dahulu.');
        }

        $sos = So::with(['details.product', 'customer'])
            ->whereIn('so_id', $soIds)
            ->get();

        $detailRows = [];
        foreach ($sos as $so) {
            foreach ($so->details as $detail) {
                $detailRows[] = [
                    'so_detail_id' => $detail->so_detail_id,
                    'so_code'      => $so->so_code,
                    'product_id'   => $detail->so_detail_id_product,
                    'product_nama' => $detail->product->product_nama ?? '-',
                    'qty'          => $detail->so_detail_qty,
                ];
            }
        }

        return view('pages.so.prepare', [
            'sos'        => $sos,
            'detailRows' => $detailRows,
            'soIds'      => $soIds,
        ]);
    }

    public function postPrepare(GeneralRequest $request)
    {
        $data = $request->validate([
            'details'                      => ['required', 'array', 'min:1'],
            'details.*.so_detail_id'       => ['required', 'integer', 'exists:detail_so,so_detail_id'],
            'details.*.product_id'         => ['required', 'integer', 'exists:product,product_id'],
            'details.*.qty'                => ['required', 'numeric', 'min:1'],
            'so_ids'                       => ['required', 'array', 'min:1'],
        ]);

        try {
            $keluar = DB::transaction(function () use ($data) {
                $totalQty = collect($data['details'])->sum('qty');

                $keluar = \App\Models\Keluar::create([
                    'out_tanggal'  => now()->toDateString(),
                    'out_status'   => 'Pending',
                    'out_reff'     => 'Prepare SO',
                    'out_qty'      => $totalQty,
                    'out_catatan'  => 'Digabung dari SO: '.implode(', ', $data['so_ids']),
                ]);

                $seq = 1;
                foreach ($data['details'] as $row) {
                    \App\Models\KeluarDetail::create([
                        'out_detail_code_keluar'    => $keluar->out_code,
                        'out_detail_id_product'     => $row['product_id'],
                        'out_detail_id_so_detail'   => $row['so_detail_id'],
                        'out_detail_code'           => sprintf('%s-%03d', $keluar->out_code, $seq),
                        'out_detail_qty'            => $row['qty'],
                        'out_detail_reff'           => SoDetail::find($row['so_detail_id'])?->so_detail_code,
                    ]);
                    $seq++;
                }

                So::whereIn('so_id', $data['so_ids'])
                    ->where('so_status', '!=', SoStatusEnum::PREPARE)
                    ->update(['so_status' => SoStatusEnum::PREPARE]);

                return $keluar;
            });

            flash()->success('Prepare SO berhasil. Keluar code: '.$keluar->out_code);
            return redirect()->route('wms-so.getTable');
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            return back()->withInput();
        }
    }

    public function getAssign(Request $request, string $soId)
    {
        $so = So::with(['customer', 'details.product'])->findOrFail($soId);

        $keluarCode = $this->keluarCodeForSo($so);
        if (!$keluarCode) {
            flash()->error('SO ini belum memiliki Keluar.');
            return redirect()->route('wms-so-prepare.index');
        }

        $keluar = Keluar::with(['details.product', 'assignments.stock.lokasi.gudang'])
            ->where('out_code', $keluarCode)
            ->firstOrFail();

        $availableStock = Stock::where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->with('lokasi.gudang')
            ->get()
            ->groupBy('stock_id_product')
            ->map(function ($stocks) {
                return $stocks->map(function ($s) {
                    $assignedQty = $s->assignments()
                        ->whereIn('stock_assignment_status', ['Pending', 'Picked'])
                        ->sum('stock_assignment_qty');
                    $remaining = max(0, (float) $s->stock_qty - $assignedQty);
                    return [
                        'stock_id'    => $s->stock_id,
                        'stock_code'  => $s->stock_code,
                        'lokasi_code' => $s->stock_code_lokasi,
                        'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_qty'   => (float) $s->stock_qty,
                        'remaining'   => $remaining,
                        'expired'     => optional($s->stock_expired_date)->format('Y-m-d'),
                    ];
                });
            });

        $existingAssignments = $keluar->details->mapWithKeys(function ($detail) {
            $assignments = $detail->assignments
                ->map(fn ($a) => [
                    'assignment_id' => $a->stock_assignment_id,
                    'stock_id'      => $a->stock_assignment_id_stock,
                    'qty'           => $a->stock_assignment_qty,
                ]);
            return [$detail->out_detail_id => $assignments];
        });

        return view('pages.so.assign', [
            'so'                    => $so,
            'keluar'                => $keluar,
            'availableStock'        => $availableStock,
            'existingAssignments'   => $existingAssignments,
        ]);
    }

    public function postAssign(Request $request, string $soId)
    {
        $data = $request->validate([
            'assignments'                        => ['required', 'array', 'min:1'],
            'assignments.*.keluar_detail_id'     => ['required', 'integer', 'exists:keluar_detail,out_detail_id'],
            'assignments.*.stock_id'             => ['required', 'integer', 'exists:stock,stock_id'],
            'assignments.*.qty'                  => ['required', 'numeric', 'min:0.001'],
            'assignments.*.so_detail_id'         => ['required', 'integer', 'exists:detail_so,so_detail_id'],
        ]);

        try {
            DB::transaction(function () use ($data, $soId) {
                $so = So::findOrFail($soId);
                $keluarCode = $this->keluarCodeForSo($so);
                if (!$keluarCode) {
                    throw new \Exception('SO ini belum memiliki Keluar.');
                }

                StockAssignment::where('stock_assignment_id_keluar', $keluarCode)->delete();

                foreach ($data['assignments'] as $row) {
                    $stock = Stock::where('stock_id', $row['stock_id'])
                        ->where('stock_type', Stock::TYPE_IN)
                        ->first();
                    if (!$stock) {
                        throw new \Exception("Stock ID {$row['stock_id']} tidak tersedia.");
                    }

                    $detail = KeluarDetail::findOrFail($row['keluar_detail_id']);
                    $alreadyAssigned = StockAssignment::where('stock_assignment_id_keluar_detail', $row['keluar_detail_id'])
                        ->sum('stock_assignment_qty');
                    $remaining = (float) $detail->out_detail_qty - $alreadyAssigned;
                    if ((float) $row['qty'] > $remaining + 0.001) {
                        throw new \Exception("Qty assign melebihi sisa kebutuhan. Sisa: {$remaining}");
                    }

                    $stockAssigned = StockAssignment::where('stock_assignment_id_stock', $row['stock_id'])
                        ->whereIn('stock_assignment_status', ['Pending', 'Picked'])
                        ->sum('stock_assignment_qty');
                    $stockRemaining = (float) $stock->stock_qty - $stockAssigned;
                    if ((float) $row['qty'] > $stockRemaining + 0.001) {
                        throw new \Exception("Stock {$stock->stock_code} tidak cukup. Tersisa: {$stockRemaining}");
                    }

                    StockAssignment::create([
                        'stock_assignment_id_keluar'         => $keluarCode,
                        'stock_assignment_id_stock'          => $row['stock_id'],
                        'stock_assignment_id_keluar_detail'  => $row['keluar_detail_id'],
                        'stock_assignment_id_so_detail'      => $row['so_detail_id'],
                        'stock_assignment_qty'               => $row['qty'],
                        'stock_assignment_status'            => 'Pending',
                    ]);
                }

                Keluar::where('out_code', $keluarCode)->update(['out_assigned' => true]);
            });

            flash()->success('Stock assignment berhasil disimpan.');
            return redirect()->route('wms-so-prepare.assign', ['soId' => $soId]);
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            return back()->withInput();
        }
    }

    public function cetak(string $id)
    {
        $so = $this->model->with(['details.product', 'customer'])->findOrFail($id);

        return view('pages.so.cetak', ['so' => $so]);
    }

    /**
     * Warehouse prepare: list SOs berstatus Prepare yang menunggu diverifikasi
     * oleh petugas warehouse (scan SO + scan barang di staging).
     */
    public function getPrepareList()
    {
        $sos = So::with(['customer', 'details.product', 'prepare'])
            ->where('so_status', SoStatusEnum::PREPARE)
            ->orderBy('so_tanggal')
            ->get()
            ->map(function (So $so) {
                $prepare = $so->prepare()->first();

                $totalQty    = (float) $so->details->sum('so_detail_qty');
                $pickedQty   = $this->stagedQtyForSo($so);
                $assignedQty = $prepare ? (float) $prepare->details->sum('so_prepare_detail_qty') : 0;

                return [
                    'so'            => $so,
                    'prepare'       => $prepare,
                    'total_qty'     => $totalQty,
                    'picked_qty'    => $pickedQty,
                    'assigned_qty'  => $assignedQty,
                    'progress'      => $totalQty > 0 ? (int) min(100, round($assignedQty / $totalQty * 100)) : 0,
                ];
            });

        return view('pages.so.prepare-list', ['rows' => $sos]);
    }

    /**
     * Warehouse prepare per-SO: tampil detail item SO + barang staging hasil forklift.
     * Petugas scan kode SO lalu scan kode barang staging untuk mengalokasikan qty.
     */
    public function getPrepareSo(Request $request, string $soId)
    {
        $so = So::with(['customer', 'details.product'])->findOrFail($soId);

        if ($so->so_status !== SoStatusEnum::PREPARE) {
            flash()->error('SO ini tidak berstatus Prepare.');
            return redirect()->route('wms-so-prepare.index');
        }

        $prepare = SoPrepare::firstOrCreate(
            ['so_prepare_id_so' => $so->so_id],
            ['so_prepare_id_keluar' => $this->keluarCodeForSo($so)]
        );

        $staged = $this->stagedRealisasi($so);
        $assigned = $prepare->details()->with('realisasi.stock.lokasi.gudang')->get();

        $assignedByRealisasi = $assigned->groupBy('so_prepare_detail_id_realisasi')
            ->map(fn ($rows) => (float) $rows->sum('so_prepare_detail_qty'));

        $stagedLines = $staged->map(function (KeluarRealisasi $r) use ($assignedByRealisasi) {
            return [
                'realisasi'      => $r,
                'stock'          => $r->stock,
                'lokasi_nama'    => $r->stock?->lokasi?->lokasi_nama ?? '-',
                'gudang_nama'    => $r->stock?->lokasi?->gudang?->gudang_nama ?? '-',
                'qty_picked'     => (float) $r->out_realisasi_qty,
                'qty_assigned'   => (float) ($assignedByRealisasi->get($r->out_realisasi_id) ?? 0),
                'qty_remaining'  => max(0, (float) $r->out_realisasi_qty - (float) ($assignedByRealisasi->get($r->out_realisasi_id) ?? 0)),
            ];
        })->values();

        return view('pages.so.prepare-so', [
            'so'           => $so,
            'prepare'      => $prepare,
            'staged_lines' => $stagedLines,
            'lines'        => $this->prepareLineStatus($so, $prepare),
        ]);
    }

    /**
     * Alokasikan barang staging ke SO. Bisa via scan barcode (stock_code / out_realisasi_code)
     * atau manual qty per baris staging.
     */
    public function postPrepareSo(GeneralRequest $request, string $soId)
    {
        $data = $request->validate([
            'stock_scan' => ['nullable', 'string'],
            'assign'     => ['nullable', 'array'],
            'assign.*'   => ['nullable', 'array'],
            'assign.*.realisasi_id' => ['nullable', 'integer'],
            'assign.*.qty'          => ['nullable', 'numeric', 'min:0'],
        ]);

        $so = So::with('details')->findOrFail($soId);

        if ($so->so_status !== SoStatusEnum::PREPARE) {
            flash()->error('SO ini tidak berstatus Prepare.');
            return redirect()->route('wms-so-prepare.index');
        }

        $prepare = SoPrepare::firstOrCreate(
            ['so_prepare_id_so' => $so->so_id],
            ['so_prepare_id_keluar' => $this->keluarCodeForSo($so)]
        );

        try {
            DB::transaction(function () use ($so, $prepare, $data) {
                if (!empty($data['stock_scan'])) {
                    $this->assignByScan($so, $prepare, trim($data['stock_scan']));
                }

                foreach ($data['assign'] ?? [] as $row) {
                    if (empty($row['realisasi_id'])) {
                        continue;
                    }
                    $qty = (float) ($row['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $this->assignRealisasi($so, $prepare, (int) $row['realisasi_id'], $qty);
                }

                if ($this->soLinesFulfilled($so, $prepare)) {
                    $prepare->update(['so_prepare_status' => SoPrepare::STATUS_DONE]);
                    $so->update(['so_status' => SoStatusEnum::CONFIRMED]);
                }
            });

            flash()->success('Alokasi prepare SO berhasil.');
            return redirect()->route('wms-so-prepare.show', ['soId' => $so->so_id]);
        } catch (\Throwable $th) {
            flash()->error($th->getMessage());
            return back()->withInput();
        }
    }

    /* ------------------------------------------------------------------
     | Helpers
     | ------------------------------------------------------------------ */

    private function keluarCodeForSo(So $so): ?string
    {
        return KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
            ->value('out_detail_code_keluar');
    }

    private function keluarCodesForSo(So $so): array
    {
        return KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
            ->pluck('out_detail_code_keluar')
            ->unique()
            ->values()
            ->all();
    }

    private function stagedRealisasi(So $so): \Illuminate\Support\Collection
    {
        $codes = $this->keluarCodesForSo($so);
        if (empty($codes)) {
            return collect();
        }

        return KeluarRealisasi::with(['stock.lokasi.gudang', 'detail'])
            ->whereHas('detail', fn ($q) => $q->whereIn('out_detail_code_keluar', $codes))
            ->get();
    }

    private function stagedQtyForSo(So $so): float
    {
        return (float) $this->stagedRealisasi($so)->sum('out_realisasi_qty');
    }

    private function prepareLineStatus(So $so, SoPrepare $prepare): array
    {
        return $so->details->map(function (SoDetail $d) use ($prepare) {
            $assigned = (float) $prepare->details()
                ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
                ->sum('so_prepare_detail_qty');

            return [
                'detail'      => $d,
                'qty_needed'  => (float) $d->so_detail_qty,
                'qty_assigned' => $assigned,
                'qty_remaining' => max(0, (float) $d->so_detail_qty - $assigned),
            ];
        })->all();
    }

    private function soLinesFulfilled(So $so, SoPrepare $prepare): bool
    {
        foreach ($so->details as $d) {
            $assigned = (float) $prepare->details()
                ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
                ->sum('so_prepare_detail_qty');

            if ($assigned + 1e-9 < (float) $d->so_detail_qty) {
                return false;
            }
        }

        return true;
    }

    private function assignRealisasi(So $so, SoPrepare $prepare, int $realisasiId, float $qty): void
    {
        $realisasi = KeluarRealisasi::with(['stock', 'detail'])->findOrFail($realisasiId);

        $codes = $this->keluarCodesForSo($so);
        if (!in_array($realisasi->detail->out_detail_code_keluar, $codes, true)) {
            throw new \RuntimeException('Barang staging bukan milik batch prepare SO ini.');
        }

        $productId = $realisasi->stock->stock_id_product;
        $line = $so->details->firstWhere('so_detail_id_product', $productId);
        if (!$line) {
            throw new \RuntimeException('Product barang staging tidak ada di SO ini.');
        }

        $assignedForLine = (float) $prepare->details()
            ->where('so_prepare_detail_id_product', $productId)
            ->sum('so_prepare_detail_qty');
        $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

        if ($qty > $lineRemaining) {
            throw new \RuntimeException('Qty melebihi sisa kebutuhan SO (sisa '.$lineRemaining.').');
        }

        $assignedForRealisasi = (float) $prepare->details()
            ->where('so_prepare_detail_id_realisasi', $realisasiId)
            ->sum('so_prepare_detail_qty');
        $pickRemaining = (float) $realisasi->out_realisasi_qty - $assignedForRealisasi;

        if ($qty > $pickRemaining) {
            throw new \RuntimeException('Qty melebihi sisa barang staging (sisa '.$pickRemaining.').');
        }

        SoPrepareDetail::create([
            'so_prepare_detail_id_prepare'   => $prepare->so_prepare_id,
            'so_prepare_detail_id_realisasi' => $realisasiId,
            'so_prepare_detail_id_product'   => $productId,
            'so_prepare_detail_qty'          => $qty,
        ]);
    }

    private function assignByScan(So $so, SoPrepare $prepare, string $scan): void
    {
        $realisasi = KeluarRealisasi::with(['stock', 'detail'])
            ->where('out_realisasi_code', $scan)
            ->orWhereHas('stock', fn ($q) => $q->where('stock_code', $scan))
            ->first();

        if (!$realisasi) {
            throw new \RuntimeException('Barcode tidak ditemukan di staging. Cek kode stock atau OUTR.');
        }

        $codes = $this->keluarCodesForSo($so);
        if (!in_array($realisasi->detail->out_detail_code_keluar, $codes, true)) {
            throw new \RuntimeException('Barang staging bukan milik batch prepare SO ini.');
        }

        $productId = $realisasi->stock->stock_id_product;
        $line = $so->details->firstWhere('so_detail_id_product', $productId);
        if (!$line) {
            throw new \RuntimeException('Product barang staging tidak ada di SO ini.');
        }

        $assignedForLine = (float) $prepare->details()
            ->where('so_prepare_detail_id_product', $productId)
            ->sum('so_prepare_detail_qty');
        $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

        if ($lineRemaining <= 0) {
            throw new \RuntimeException('Qty SO untuk product ini sudah terpenuhi.');
        }

        $assignedForRealisasi = (float) $prepare->details()
            ->where('so_prepare_detail_id_realisasi', $realisasi->out_realisasi_id)
            ->sum('so_prepare_detail_qty');
        $pickRemaining = (float) $realisasi->out_realisasi_qty - $assignedForRealisasi;

        $qty = min($lineRemaining, $pickRemaining);

        if ($qty <= 0) {
            throw new \RuntimeException('Barang staging ini sudah habis dialokasikan.');
        }

        SoPrepareDetail::create([
            'so_prepare_detail_id_prepare'   => $prepare->so_prepare_id,
            'so_prepare_detail_id_realisasi' => $realisasi->out_realisasi_id,
            'so_prepare_detail_id_product'   => $productId,
            'so_prepare_detail_qty'          => $qty,
        ]);
    }
}
