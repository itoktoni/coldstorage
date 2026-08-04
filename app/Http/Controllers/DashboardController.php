<?php

namespace App\Http\Controllers;

use App\Models\ForkliftTask;
use App\Models\Gudang;
use App\Models\Keluar;
use App\Models\MasukDetail;
use App\Models\Product;
use App\Models\Split;
use App\Models\Stock;
use App\Models\StockLog;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $today = now()->toDateString();

        $stats = [
            'total_stock' => Stock::where('stock_type', Stock::TYPE_IN)->sum('stock_qty'),
            'total_product' => Product::where('product_status', 'active')->count(),
            'inbound_today' => MasukDetail::where('in_detail_tanggal', $today)->count(),
            'outbound_today' => Keluar::where('out_tanggal', $today)->count(),
            'pending_forklift' => ForkliftTask::where('forklift_status', 'Pending')->count(),
            'pending_split' => Split::where('split_status', 'Draft')->count(),
        ];

        $warehouses = Gudang::with(['lokasi' => function ($q) {
            $q->whereNull('lokasi_category')->orWhere('lokasi_category', '!=', 'staging');
        }])->get()->map(function ($gudang) {
            $lokasiData = $gudang->lokasi->map(function ($lokasi) {
                $currentQty = $lokasi->stock()->where('stock_type', 'IN')->where('stock_qty', '>', 0)->sum('stock_qty');
                $maxQty = $lokasi->lokasi_max_qty;
                $percent = $maxQty > 0 ? round(($currentQty / $maxQty) * 100) : 0;

                return [
                    'code' => $lokasi->lokasi_code,
                    'name' => $lokasi->lokasi_nama,
                    'current_qty' => $currentQty,
                    'max_qty' => $maxQty,
                    'percent' => $percent,
                    'category' => $lokasi->lokasi_category,
                ];
            });

            $totalCurrent = $lokasiData->sum('current_qty');
            $totalMax = $lokasiData->sum('max_qty');
            $totalPercent = $totalMax > 0 ? round(($totalCurrent / $totalMax) * 100) : 0;

            return [
                'code' => $gudang->gudang_code,
                'name' => $gudang->gudang_nama,
                'total_current' => $totalCurrent,
                'total_max' => $totalMax,
                'total_percent' => $totalPercent,
                'lokasi' => $lokasiData,
            ];
        });

        $recentLogs = StockLog::with(['product', 'lokasi'])
            ->latest('created_at')
            ->limit(8)
            ->get()
            ->map(fn ($log) => [
                'icon' => match ($log->stock_type) {
                    'IN' => 'login',
                    'OUT' => 'logout',
                    'STAGING' => 'swap_horiz',
                    default => 'sync',
                },
                'iconBg' => match ($log->stock_type) {
                    'IN' => 'bg-success/10',
                    'OUT' => 'bg-error/10',
                    'STAGING' => 'bg-warning/10',
                    default => 'bg-primary/10',
                },
                'iconColor' => match ($log->stock_type) {
                    'IN' => 'text-success',
                    'OUT' => 'text-error',
                    'STAGING' => 'text-warning',
                    default => 'text-primary',
                },
                'title' => $log->product->product_nama ?? '-',
                'subtitle' => $log->lokasi->lokasi_nama ?? $log->stock_code_lokasi,
                'qty' => $log->stock_qty,
                'type' => $log->stock_type,
                'time' => $log->created_at?->diffForHumans() ?? '',
            ]);

        $lowStock = Stock::with(['product', 'lokasi'])
            ->where('stock_type', Stock::TYPE_IN)
            ->where('stock_qty', '>', 0)
            ->where('stock_qty', '<=', 5)
            ->orderBy('stock_qty')
            ->limit(5)
            ->get();

        $pendingForklift = ForkliftTask::where('forklift_status', 'Pending')
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'stats',
            'warehouses',
            'recentLogs',
            'lowStock',
            'pendingForklift'
        ));
    }
}
