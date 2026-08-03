<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Http\Request;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $query = StockLog::with(['product', 'lokasi']);

        if ($request->filled('product_id')) {
            $query->where('stock_id_product', $request->input('product_id'));
        }

        if ($request->filled('lokasi_code')) {
            $query->where('stock_code_lokasi', $request->input('lokasi_code'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        return view('pages.stock-card.index', [
            'logs'           => $logs,
            'productOptions' => Product::orderBy('product_nama')->pluck('product_nama', 'product_id'),
            'filters'        => $request->only(['product_id', 'lokasi_code', 'action', 'date_from', 'date_to']),
        ]);
    }
}
