<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralRequest;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\Kendaraan;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
use App\Models\SoPrepare;
use App\Models\SoPrepareDetail;
use App\Models\Stock;
use App\Models\StockAssignment;
use App\Models\Supir;
use App\Wms\SoStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            'model' => $this->model,
            'productOptions' => Product::pluck('product_nama', 'product_id'),
            'productPrices' => Product::pluck('product_harga', 'product_id'),
            'customerOptions' => So::customerOptions(),
            'statusOptions' => So::statusOptions(),
            'availableStock' => $availableStock,
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
        } catch (ValidationException $e) {
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
        } catch (ValidationException $e) {
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
                'so_detail_id_so' => $so->so_id,
                'so_detail_id_product' => $productId,
                'so_detail_qty' => $qty,
                'so_detail_harga' => $prices[$productId] ?? 0,
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

        $productCodes = Product::whereIn('product_id', $grouped->keys())
            ->pluck('product_code', 'product_id');

        $timestamp = now()->format('YmdHis');

        foreach ($grouped as $productId => $qty) {
            if ($qty <= 0) {
                continue;
            }
            $productCode = $productCodes[$productId] ?? 'PROD-'.str_pad($productId, 2, '0', STR_PAD_LEFT);
            $stockCode = implode('#', [
                $productCode,
                $timestamp.strtoupper(uniqid()),
                (string) $qty,
                'RESERVE',
            ]);

            Stock::create([
                'stock_code' => $stockCode,
                'stock_id_product' => (int) $productId,
                'stock_qty' => (float) $qty,
                'stock_type' => Stock::TYPE_RESERVE,
                'stock_reff' => $so->so_code,
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
                throw ValidationException::withMessages([
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
                    'so_code' => $so->so_code,
                    'product_id' => $detail->so_detail_id_product,
                    'product_nama' => $detail->product->product_nama ?? '-',
                    'qty' => $detail->so_detail_qty,
                ];
            }
        }

        return view('pages.so.prepare', [
            'sos' => $sos,
            'detailRows' => $detailRows,
            'soIds' => $soIds,
        ]);
    }

    public function postPrepare(GeneralRequest $request)
    {
        $data = $request->validate([
            'details' => ['required', 'array', 'min:1'],
            'details.*.so_detail_id' => ['required', 'integer', 'exists:detail_so,so_detail_id'],
            'details.*.product_id' => ['required', 'integer', 'exists:product,product_id'],
            'details.*.qty' => ['required', 'numeric', 'min:1'],
            'so_ids' => ['required', 'array', 'min:1'],
        ]);

        try {
            $keluar = DB::transaction(function () use ($data) {
                $totalQty = collect($data['details'])->sum('qty');

                $keluar = Keluar::create([
                    'out_tanggal' => now()->toDateString(),
                    'out_status' => 'Pending',
                    'out_reff' => 'Prepare SO',
                    'out_qty' => $totalQty,
                    'out_catatan' => 'Digabung dari SO: '.implode(', ', $data['so_ids']),
                ]);

                $seq = 1;
                foreach ($data['details'] as $row) {
                    KeluarDetail::create([
                        'out_detail_code_keluar' => $keluar->out_code,
                        'out_detail_id_product' => $row['product_id'],
                        'out_detail_id_so_detail' => $row['so_detail_id'],
                        'out_detail_code' => sprintf('%s-%03d', $keluar->out_code, $seq),
                        'out_detail_qty' => $row['qty'],
                        'out_detail_reff' => SoDetail::find($row['so_detail_id'])?->so_detail_code,
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
        if (! $keluarCode) {
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
                        'stock_id' => $s->stock_id,
                        'stock_code' => $s->stock_code,
                        'lokasi_code' => $s->stock_code_lokasi,
                        'lokasi_nama' => $s->lokasi?->lokasi_nama ?? '-',
                        'gudang_nama' => $s->lokasi?->gudang?->gudang_nama ?? '-',
                        'stock_qty' => (float) $s->stock_qty,
                        'remaining' => $remaining,
                        'expired' => optional($s->stock_expired_date)->format('Y-m-d'),
                    ];
                });
            });

        $existingAssignments = $keluar->details->mapWithKeys(function ($detail) {
            $assignments = $detail->assignments
                ->map(fn ($a) => [
                    'assignment_id' => $a->stock_assignment_id,
                    'stock_id' => $a->stock_assignment_id_stock,
                    'qty' => $a->stock_assignment_qty,
                ]);

            return [$detail->out_detail_id => $assignments];
        });

        return view('pages.so.assign', [
            'so' => $so,
            'keluar' => $keluar,
            'availableStock' => $availableStock,
            'existingAssignments' => $existingAssignments,
        ]);
    }

    public function postAssign(Request $request, string $soId)
    {
        $data = $request->validate([
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.keluar_detail_id' => ['required', 'integer', 'exists:keluar_detail,out_detail_id'],
            'assignments.*.stock_id' => ['required', 'integer', 'exists:stock,stock_id'],
            'assignments.*.qty' => ['required', 'numeric', 'min:0.001'],
            'assignments.*.so_detail_id' => ['required', 'integer', 'exists:detail_so,so_detail_id'],
        ]);

        try {
            DB::transaction(function () use ($data, $soId) {
                $so = So::findOrFail($soId);
                $keluarCode = $this->keluarCodeForSo($so);
                if (! $keluarCode) {
                    throw new \Exception('SO ini belum memiliki Keluar.');
                }

                StockAssignment::where('stock_assignment_id_keluar', $keluarCode)->delete();

                foreach ($data['assignments'] as $row) {
                    $stock = Stock::where('stock_id', $row['stock_id'])
                        ->where('stock_type', Stock::TYPE_IN)
                        ->first();
                    if (! $stock) {
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
                        'stock_assignment_id_keluar' => $keluarCode,
                        'stock_assignment_id_stock' => $row['stock_id'],
                        'stock_assignment_id_keluar_detail' => $row['keluar_detail_id'],
                        'stock_assignment_id_so_detail' => $row['so_detail_id'],
                        'stock_assignment_qty' => $row['qty'],
                        'stock_assignment_status' => 'Pending',
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
     * Cetak Invoice (real qty dari prepare).
     */
    public function cetakInvoice(string $id)
    {
        $invoice = Invoice::with(['so', 'customer', 'details.product'])
            ->where('invoice_id_so', $id)
            ->first();

        if (! $invoice) {
            flash()->error('Invoice belum dibuat untuk SO ini. Silakan kirim SO terlebih dahulu.');

            return redirect()->route('wms-so.getTable');
        }

        return view('pages.so.cetak-invoice', ['invoice' => $invoice]);
    }

    /**
     * Cetak Performance Invoice (perbandingan SO vs real qty).
     */
    public function cetakPerformance(string $id)
    {
        $so = So::with(['customer', 'prepare.details.product'])->findOrFail($id);

        $prepare = $so->prepare;
        if (! $prepare) {
            flash()->error('SO ini belum memiliki data prepare.');

            return back();
        }

        // Build performance data: order qty vs real qty per product
        $orderQtys = $so->details->pluck('so_detail_qty', 'so_detail_id_product');
        $realQtys = $prepare->details()
            ->selectRaw('so_prepare_detail_id_product, SUM(so_prepare_detail_qty) as total')
            ->groupBy('so_prepare_detail_id_product')
            ->pluck('total', 'so_detail_id_product');

        $performance = $so->details->map(function ($detail) use ($realQtys) {
            $productId = $detail->so_detail_id_product;

            return [
                'product_kode' => $detail->product->product_kode ?? '-',
                'product_nama' => $detail->product->product_nama ?? '-',
                'order_qty' => (float) $detail->so_detail_qty,
                'real_qty' => (float) ($realQtys->get($productId) ?? 0),
            ];
        });

        return view('pages.so.cetak-performance', [
            'so' => $so,
            'performance' => $performance,
        ]);
    }

    /**
     * Cetak Delivery Order.
     */
    public function cetakDelivery(string $id)
    {
        $delivery = Delivery::with(['so.customer', 'invoice'])
            ->where('delivery_id_so', $id)
            ->first();

        if (! $delivery) {
            flash()->error('Delivery Order belum dibuat untuk SO ini. Silakan kirim SO terlebih dahulu.');

            return redirect()->route('wms-so.getTable');
        }

        // Get real qty from prepare details
        $prepare = $delivery->so->prepare;
        $realQtys = $prepare
            ? $prepare->details()
                ->selectRaw('so_prepare_detail_id_product, SUM(so_prepare_detail_qty) as total')
                ->groupBy('so_prepare_detail_id_product')
                ->pluck('total', 'so_prepare_detail_id_product')
            : collect();

        $details = $delivery->so->details->map(function ($d) use ($realQtys) {
            return [
                'product_nama' => $d->product->product_nama ?? '-',
                'real_qty' => (float) ($realQtys->get($d->so_detail_id_product) ?? 0),
            ];
        });

        return view('pages.so.cetak-delivery', [
            'delivery' => $delivery,
            'details' => $details,
        ]);
    }

    /**
     * Ship SO: show form with delivery details.
     */
    public function ship(string $id)
    {
        $so = So::with(['customer', 'prepare.details.product'])->findOrFail($id);

        if ($so->so_status !== SoStatusEnum::CONFIRMED) {
            flash()->error('SO ini belum berstatus Confirmed.');

            return back();
        }

        $prepare = $so->prepare;
        if (! $prepare) {
            flash()->error('SO ini belum memiliki data prepare.');

            return back();
        }

        // Build real qty data per product
        $realQtys = $prepare->details()
            ->selectRaw('so_prepare_detail_id_product, SUM(so_prepare_detail_qty) as total')
            ->groupBy('so_prepare_detail_id_product')
            ->pluck('total', 'so_prepare_detail_id_product');

        $details = $so->details->map(function ($d) use ($realQtys) {
            return [
                'product_nama' => $d->product->product_nama ?? '-',
                'order_qty' => (float) $d->so_detail_qty,
                'real_qty' => (float) ($realQtys->get($d->so_detail_id_product) ?? 0),
                'harga' => $d->harga,
            ];
        });

        return view('pages.so.form-ship', [
            'so' => $so,
            'details' => $details,
            'kendaraans' => Kendaraan::where('kendaraan_aktif', true)->get(),
            'supirs' => Supir::where('supir_aktif', true)->get(),
        ]);
    }

    /**
     * Store ship: create invoice + delivery order.
     */
    public function storeShip(GeneralRequest $request, string $id)
    {
        $data = $request->validate([
            'delivery_nama_penerima' => ['nullable', 'string', 'max:255'],
            'delivery_alamat_tujuan' => ['nullable', 'string'],
            'delivery_id_kendaraan' => ['nullable', 'exists:kendaraan,id'],
            'delivery_id_supir' => ['nullable', 'exists:supir,id'],
            'delivery_plat_kendaraan' => ['nullable', 'string', 'max:50'],
            'delivery_nama_kurir' => ['nullable', 'string', 'max:255'],
            'delivery_catatan' => ['nullable', 'string'],
        ]);

        $so = So::with(['customer', 'prepare.details.product'])->findOrFail($id);

        if ($so->so_status !== SoStatusEnum::CONFIRMED) {
            flash()->error('SO ini belum berstatus Confirmed.');

            return back();
        }

        $prepare = $so->prepare;
        if (! $prepare) {
            flash()->error('SO ini belum memiliki data prepare.');

            return back();
        }

        // Check if invoice already exists with actual amounts
        $existingInvoice = Invoice::where('invoice_id_so', $so->so_id)->first();
        if ($existingInvoice && (float) $existingInvoice->invoice_subtotal > 0) {
            flash()->error('Invoice sudah dibuat untuk SO ini.');

            return back();
        }

        // Delete zero-value invoice if exists (allows re-ship)
        if ($existingInvoice) {
            InvoiceDetail::where('invoice_detail_id_invoice', $existingInvoice->invoice_id)->delete();
            $existingInvoice->delete();
        }

        try {
            DB::transaction(function () use ($so, $prepare, $data) {
                // 1. Create Invoice from real qty
                $productQtys = $prepare->details()
                    ->selectRaw('so_prepare_detail_id_product, SUM(so_prepare_detail_qty) as total_qty')
                    ->groupBy('so_prepare_detail_id_product')
                    ->pluck('total_qty', 'so_prepare_detail_id_product');

                $subtotal = 0;
                $detailRows = [];

                foreach ($productQtys as $productId => $realQty) {
                    $realQty = (float) $realQty;
                    if ($realQty <= 0) {
                        continue;
                    }

                    $soDetail = $so->details->firstWhere('so_detail_id_product', $productId);
                    $harga = $soDetail ? $soDetail->harga : 0;
                    $lineSubtotal = $realQty * $harga;
                    $subtotal += $lineSubtotal;

                    $detailRows[] = [
                        'invoice_detail_id_product' => $productId,
                        'invoice_detail_qty' => $realQty,
                        'invoice_detail_harga' => $harga,
                        'invoice_detail_subtotal' => $lineSubtotal,
                    ];
                }

                $ppn = $subtotal * 0.11;
                $total = $subtotal + $ppn;

                $invoice = Invoice::create([
                    'invoice_tanggal' => now()->toDateString(),
                    'invoice_id_so' => $so->so_id,
                    'invoice_id_customer' => $so->so_id_customer,
                    'invoice_subtotal' => $subtotal,
                    'invoice_ppn' => $ppn,
                    'invoice_total' => $total,
                    'invoice_status' => 'Unpaid',
                ]);

                foreach ($detailRows as $row) {
                    $row['invoice_detail_id_invoice'] = $invoice->invoice_id;
                    InvoiceDetail::create($row);
                }

                // 2. Create Delivery Order
                $deliveryData = $data;
                if (! empty($data['delivery_id_supir'])) {
                    $supir = Supir::find($data['delivery_id_supir']);
                    $deliveryData['delivery_nama_driver'] = $supir?->supir_nama ?? $data['delivery_nama_driver'] ?? null;
                }
                if (! empty($data['delivery_id_kendaraan'])) {
                    $kendaraan = Kendaraan::find($data['delivery_id_kendaraan']);
                    $deliveryData['delivery_plat_kendaraan'] = $kendaraan?->kendaraan_plat ?? $data['delivery_plat_kendaraan'] ?? null;
                }
                Delivery::create(array_merge($deliveryData, [
                    'delivery_tanggal' => now()->toDateString(),
                    'delivery_id_so' => $so->so_id,
                    'delivery_id_invoice' => $invoice->invoice_id,
                    'delivery_status' => 'Pending',
                ]));

                // 3. Delete staging stocks for this SO's keluar codes
                $keluarCodes = KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
                    ->pluck('out_detail_code_keluar')
                    ->unique();
                Stock::where('stock_type', Stock::TYPE_STAGING)
                    ->whereIn('stock_reff', $keluarCodes)
                    ->delete();

                // 4. Update SO status
                $so->update(['so_status' => SoStatusEnum::SHIPPED]);
            });

            flash()->success('SO berhasil dikirim. Invoice & Delivery Order telah dibuat.');

            return redirect()->route('wms-so.getTable');
        } catch (\Throwable $th) {
            flash()->error('Gagal mengirim SO: '.$th->getMessage());

            return back();
        }
    }

    /**
     * Warehouse prepare: list SOs berstatus Prepare yang menunggu diverifikasi
     * oleh petugas warehouse (scan SO + scan barang di staging).
     */
    public function getPrepareList()
    {
        $sos = So::with(['customer', 'details.product', 'prepare'])
            ->whereIn('so_status', [SoStatusEnum::PREPARE, SoStatusEnum::CONFIRMED])
            ->orderBy('so_status')
            ->orderBy('so_tanggal')
            ->get()
            ->map(function (So $so) {
                $prepare = $so->prepare()->first();

                $totalQty = (float) $so->details->sum('so_detail_qty');
                $pickedQty = $this->stagedQtyForSo($so);
                $assignedQty = $prepare ? (float) $prepare->details->sum('so_prepare_detail_qty') : 0;

                return [
                    'so' => $so,
                    'prepare' => $prepare,
                    'total_qty' => $totalQty,
                    'picked_qty' => $pickedQty,
                    'assigned_qty' => $assignedQty,
                    'progress' => $totalQty > 0 ? (int) min(100, round($assignedQty / $totalQty * 100)) : 0,
                    'is_done' => $so->so_status === SoStatusEnum::CONFIRMED,
                ];
            });

        return view('pages.so.prepare-list', ['rows' => $sos]);
    }

    /**
     * Warehouse prepare per-SO: tampil detail item SO + stock staging.
     * Petugas scan stock_code dari barcode staging untuk mengalokasikan qty.
     */
    public function getPrepareSo(Request $request, string $soId)
    {
        $so = So::with(['customer', 'details.product'])->findOrFail($soId);

        if ($so->so_status !== SoStatusEnum::PREPARE) {
            flash()->error('SO ini tidak berstatus Prepare.');

            return redirect()->route('wms-so-prepare.index');
        }

        return view('pages.so.prepare-so', ['soId' => $soId]);
    }

    /**
     * Alokasikan barang staging ke SO. Scan stock_code dari barcode staging.
     */
    public function postPrepareSo(GeneralRequest $request, string $soId)
    {
        $data = $request->validate([
            'stock_scan' => ['nullable', 'string'],
            'assign' => ['nullable', 'array'],
            'assign.*' => ['nullable', 'array'],
            'assign.*.stock_id' => ['nullable', 'integer'],
            'assign.*.qty' => ['nullable', 'numeric', 'min:0'],
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
                if (! empty($data['stock_scan'])) {
                    $this->assignByScan($so, $prepare, trim($data['stock_scan']));
                }

                foreach ($data['assign'] ?? [] as $row) {
                    if (empty($row['stock_id'])) {
                        continue;
                    }
                    $qty = (float) ($row['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }
                    $this->assignStock($so, $prepare, (int) $row['stock_id'], $qty);
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

    private function stagedStockForSo(So $so): Collection
    {
        $productIds = $so->details->pluck('so_detail_id_product');
        $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();

        $stocks = Stock::where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_qty', '>', 0)
            ->whereIn('stock_id_product', $productIds)
            ->with('lokasi.gudang')
            ->get();

        $assignedByStock = $prepare
            ? $prepare->details()
                ->selectRaw('so_prepare_detail_id_stock, SUM(so_prepare_detail_qty) as total')
                ->groupBy('so_prepare_detail_id_stock')
                ->pluck('total', 'so_prepare_detail_id_stock')
            : collect();

        return $stocks->map(function ($stock) use ($assignedByStock) {
            $assigned = (float) ($assignedByStock->get($stock->stock_id) ?? 0);

            return [
                'stock_id' => $stock->stock_id,
                'stock_code' => $stock->stock_code,
                'product' => $stock->product,
                'lokasi_nama' => $stock->lokasi?->lokasi_nama ?? '-',
                'gudang_nama' => $stock->lokasi?->gudang?->gudang_nama ?? '-',
                'stock_qty' => (float) $stock->stock_qty,
                'qty_assigned' => $assigned,
                'qty_remaining' => max(0, (float) $stock->stock_qty - $assigned),
            ];
        });
    }

    private function stagedQtyForSo(So $so): float
    {
        return (float) $this->stagedStockForSo($so)->sum('stock_qty');
    }

    private function prepareLineStatus(So $so, SoPrepare $prepare): array
    {
        return $so->details->map(function (SoDetail $d) use ($prepare) {
            $assigned = (float) $prepare->details()
                ->where('so_prepare_detail_id_product', $d->so_detail_id_product)
                ->sum('so_prepare_detail_qty');

            return [
                'detail' => $d,
                'qty_needed' => (float) $d->so_detail_qty,
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

    private function assignStock(So $so, SoPrepare $prepare, int $stockId, float $qty): void
    {
        $stock = Stock::where('stock_id', $stockId)
            ->where('stock_type', Stock::TYPE_STAGING)
            ->first();

        if (! $stock) {
            throw new \RuntimeException('Stock tidak ditemukan di staging.');
        }

        $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
        if (! $line) {
            throw new \RuntimeException('Product tidak ada di SO ini.');
        }

        $assignedForLine = (float) $prepare->details()
            ->where('so_prepare_detail_id_product', $stock->stock_id_product)
            ->sum('so_prepare_detail_qty');
        $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

        if ($qty > $lineRemaining + 0.001) {
            throw new \RuntimeException('Qty melebihi sisa kebutuhan SO. Sisa: '.$lineRemaining);
        }

        $assignedForStock = (float) $prepare->details()
            ->where('so_prepare_detail_id_stock', $stockId)
            ->sum('so_prepare_detail_qty');
        $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

        if ($qty > $stockRemaining + 0.001) {
            throw new \RuntimeException('Qty melebihi sisa stock staging. Sisa: '.$stockRemaining);
        }

        $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
            ->where('out_detail_id_product', $stock->stock_id_product)
            ->first();

        $realisasiId = null;
        if ($keluarDetail) {
            $realisasi = KeluarRealisasi::create([
                'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
                'out_realisasi_id_stock' => $stock->stock_id,
                'out_realisasi_qty' => $qty,
            ]);
            $realisasiId = $realisasi->out_realisasi_id;
        }

        SoPrepareDetail::create([
            'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
            'so_prepare_detail_id_realisasi' => $realisasiId,
            'so_prepare_detail_id_product' => $stock->stock_id_product,
            'so_prepare_detail_id_stock' => $stock->stock_id,
            'so_prepare_detail_qty' => $qty,
        ]);

        Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);
    }

    private function assignByScan(So $so, SoPrepare $prepare, string $scan): void
    {
        $stock = Stock::where('stock_code', $scan)
            ->where('stock_type', Stock::TYPE_STAGING)
            ->where('stock_qty', '>', 0)
            ->first();

        if (! $stock) {
            throw new \RuntimeException('Stock tidak ditemukan di staging.');
        }

        $line = $so->details->firstWhere('so_detail_id_product', $stock->stock_id_product);
        if (! $line) {
            throw new \RuntimeException('Product tidak ada di SO ini.');
        }

        $assignedForLine = (float) $prepare->details()
            ->where('so_prepare_detail_id_product', $stock->stock_id_product)
            ->sum('so_prepare_detail_qty');
        $lineRemaining = (float) $line->so_detail_qty - $assignedForLine;

        if ($lineRemaining <= 0) {
            throw new \RuntimeException('Kebutuhan SO untuk product ini sudah terpenuhi.');
        }

        $assignedForStock = (float) $prepare->details()
            ->where('so_prepare_detail_id_stock', $stock->stock_id)
            ->sum('so_prepare_detail_qty');
        $stockRemaining = (float) $stock->stock_qty - $assignedForStock;

        if ($stockRemaining <= 0) {
            throw new \RuntimeException('Stock ini sudah habis dialokasikan.');
        }

        $qty = min($lineRemaining, $stockRemaining);

        $keluarDetail = KeluarDetail::where('out_detail_code_keluar', $this->keluarCodeForSo($so))
            ->where('out_detail_id_product', $stock->stock_id_product)
            ->first();

        $realisasiId = null;
        if ($keluarDetail) {
            $realisasi = KeluarRealisasi::create([
                'out_realisasi_id_detail' => $keluarDetail->out_detail_id,
                'out_realisasi_id_stock' => $stock->stock_id,
                'out_realisasi_qty' => $qty,
            ]);
            $realisasiId = $realisasi->out_realisasi_id;
        }

        SoPrepareDetail::create([
            'so_prepare_detail_id_prepare' => $prepare->so_prepare_id,
            'so_prepare_detail_id_realisasi' => $realisasiId,
            'so_prepare_detail_id_product' => $stock->stock_id_product,
            'so_prepare_detail_id_stock' => $stock->stock_id,
            'so_prepare_detail_qty' => $qty,
        ]);

        Stock::where('stock_id', $stock->stock_id)->decrement('stock_qty', $qty);
    }
}
