<?php

namespace App\Http\Controllers\Wms;

use App\Concerns\ControllerTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralRequest;
use App\Models\Product;
use App\Models\So;
use App\Models\SoDetail;
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
                foreach ($so->details as $detail) {
                    Stock::release((int) $detail->so_detail_id_product, (float) $detail->so_detail_qty);
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

        $grouped = [];
        foreach ($sos as $so) {
            foreach ($so->details as $detail) {
                $productId = $detail->so_detail_id_product;
                if (!isset($grouped[$productId])) {
                    $grouped[$productId] = [
                        'product_id'   => $productId,
                        'product_nama' => $detail->product->product_nama ?? '-',
                        'qty'          => 0,
                        'so_codes'     => [],
                    ];
                }
                $grouped[$productId]['qty'] += $detail->so_detail_qty;
                $grouped[$productId]['so_codes'][] = $so->so_code;
            }
        }

        return view('pages.so.prepare', [
            'sos'       => $sos,
            'grouped'   => array_values($grouped),
            'soIds'     => $soIds,
        ]);
    }

    public function postPrepare(GeneralRequest $request)
    {
        $data = $request->validate([
            'details'                      => ['required', 'array', 'min:1'],
            'details.*.product_id'         => ['required', 'integer', 'exists:product,product_id'],
            'details.*.qty'                => ['required', 'numeric', 'min:1'],
            'details.*.reff'               => ['nullable', 'string'],
            'so_ids'                       => ['required', 'array', 'min:1'],
        ]);

        try {
            $keluar = DB::transaction(function () use ($data) {
                $keluar = \App\Models\Keluar::create([
                    'out_tanggal'  => now()->toDateString(),
                    'out_status'   => 'Pending',
                    'out_reff'     => 'Prepare SO',
                    'out_catatan'  => 'Digabung dari SO: '.implode(', ', $data['so_ids']),
                ]);

                $seq = 1;
                foreach ($data['details'] as $row) {
                    \App\Models\KeluarDetail::create([
                        'out_detail_code_keluar' => $keluar->out_code,
                        'out_detail_id_product'  => $row['product_id'],
                        'out_detail_code'        => sprintf('%s-%03d', $keluar->out_code, $seq),
                        'out_detail_qty'         => $row['qty'],
                        'out_detail_reff'        => $row['reff'] ?? null,
                    ]);
                    $seq++;
                }

                So::whereIn('so_id', $data['so_ids'])
                    ->where('so_status', '!=', 'Prepare')
                    ->update(['so_status' => 'Prepare']);

                return $keluar;
            });

            flash()->success('Prepare SO berhasil. Keluar code: '.$keluar->out_code);
            return redirect()->route('wms-so.getTable');
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
}
