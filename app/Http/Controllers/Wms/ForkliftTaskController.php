<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Lokasi;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForkliftTaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tasks = ForkliftTask::where('forklift_status', 'Pending')
            ->orWhere(function ($q) use ($user) {
                $q->where('forklift_status', 'Progress')
                  ->where('forklift_operator', $user->name);
            })
            ->with(['lokasiAsal', 'lokasiTujuan'])
            ->orderBy('forklift_id')
            ->get();

        return view('pages.forklift-task.index', [
            'tasks' => $tasks,
        ]);
    }

    public function scan(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $code = trim($request->code);
        $prefix = config('scan.prefix.pallet', 'P');
        $locationPrefix = config('scan.prefix.location', 'L');

        // Try to find a task by pallet: match raw code first, then prefix-stripped.
        // Pallet codes (PAL-xxx) naturally start with "P", so blind stripping breaks them.
        $palletCandidates = array_unique([
            $code,
            ($prefix && str_starts_with($code, $prefix)) ? substr($code, strlen($prefix)) : $code,
        ]);
        $matchedPallet = ForkliftTask::whereIn('forklift_pallet_code', $palletCandidates)
            ->where('forklift_status', 'Pending')
            ->orderByRaw('CASE WHEN forklift_pallet_code = ? THEN 0 ELSE 1 END', [$code])
            ->value('forklift_pallet_code');

        $isPallet = $matchedPallet !== null;
        $isLocation = !$isPallet && $locationPrefix && str_starts_with($code, $locationPrefix);

        try {
            return DB::transaction(function () use ($code, $isPallet, $isLocation, $matchedPallet, $prefix, $locationPrefix) {
                $user = Auth::user();

                if ($isPallet) {
                    $palletCode = $matchedPallet;
                    $task = ForkliftTask::where('forklift_pallet_code', $palletCode)
                        ->where('forklift_status', 'Pending')
                        ->lockForUpdate()
                        ->first();

                    if (!$task) {
                        return response()->json(['ok' => false, 'message' => 'Task tidak ditemukan atau sudah dikerjakan operator lain.'], 422);
                    }

                    $task->update([
                        'forklift_status'       => 'Progress',
                        'forklift_operator'     => $user->name,
                        'forklift_scan_asal_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Pallet ' . $palletCode . ' sedang dikerjakan oleh ' . $user->name,
                        'task_type' => $task->forklift_type,
                        'next_scan' => 'location',
                        'task_id'   => $task->forklift_id,
                    ]);
                }

                if ($isLocation) {
                    // Location codes (LOC-xx) start with "L", so try raw then prefix-stripped.
                    $stripped = ($locationPrefix && str_starts_with($code, $locationPrefix)) ? substr($code, strlen($locationPrefix)) : $code;
                    $lokasi = Lokasi::find($code) ?? Lokasi::find($stripped);
                    if (!$lokasi) {
                        return response()->json(['ok' => false, 'message' => 'Lokasi tidak ditemukan.'], 422);
                    }
                    $locationCode = $lokasi->lokasi_code;

                    $task = ForkliftTask::where('forklift_status', 'Progress')
                        ->where('forklift_operator', $user->name)
                        ->lockForUpdate()
                        ->first();

                    if (!$task) {
                        return response()->json(['ok' => false, 'message' => 'Tidak ada task Progress untuk Anda. Scan pallet dulu.'], 422);
                    }

                    if ($task->forklift_type === 'putaway') {
                        if ($locationCode !== $task->forklift_lokasi_tujuan) {
                            $expected = $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan;
                            return response()->json(['ok' => false, 'message' => 'Rak tidak sesuai. Harus scan "' . $expected . '".'], 422);
                        }

                        Stock::where('stock_reff', $task->forklift_pallet_code)
                            ->update([
                                'stock_type'         => Stock::TYPE_IN,
                                'stock_code_lokasi'  => $locationCode,
                                'stock_pallet_code'  => $task->forklift_pallet_code,
                            ]);
                    } else {
                        if (strtolower($lokasi->lokasi_category ?? '') !== 'staging') {
                            return response()->json(['ok' => false, 'message' => 'Lokasi bukan staging area.'], 422);
                        }

                        Stock::where('stock_reff', $task->forklift_pallet_code)
                            ->update([
                                'stock_type'        => Stock::TYPE_STAGING,
                                'stock_code_lokasi' => $locationCode,
                            ]);
                    }

                    $task->update([
                        'forklift_lokasi_final'   => $locationCode,
                        'forklift_status'         => 'Done',
                        'forklift_scan_tujuan_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Task ' . $task->forklift_type . ' selesai! Pallet ' . $task->forklift_pallet_code . ' di ' . $locationCode,
                    ]);
                }

                return response()->json(['ok' => false, 'message' => 'Kode tidak dikenal. Scan kode pallet (P...) atau lokasi (L...).'], 422);
            });
        } catch (\Throwable $th) {
            return response()->json(['ok' => false, 'message' => $th->getMessage()], 500);
        }
    }
}