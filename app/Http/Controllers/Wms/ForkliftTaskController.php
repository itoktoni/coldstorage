<?php

namespace App\Http\Controllers\Wms;

use App\Http\Controllers\Controller;
use App\Models\ForkliftTask;
use App\Models\Lokasi;
use App\Models\MasukDetail;
use App\Models\Po;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ForkliftTaskController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tasks = ForkliftTask::where(function ($q) use ($user) {
            // Putaway: show pending
            $q->where(function ($q2) {
                $q2->where('forklift_type', 'putaway')
                    ->where('forklift_status', 'Pending');
            });
            // Pick: show pending (orphaned tasks cleaned up by postPrepare)
            $q->orWhere(function ($q2) {
                $q2->where('forklift_type', 'pick')
                    ->where('forklift_status', 'Pending');
            });
            // Any: in progress by me
            $q->orWhere(function ($q2) use ($user) {
                $q2->where('forklift_status', 'Progress')
                    ->where('forklift_operator', $user->name);
            });
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
        $isLocation = ! $isPallet && $locationPrefix && str_starts_with($code, $locationPrefix);

        try {
            return DB::transaction(function () use ($code, $isPallet, $isLocation, $matchedPallet, $locationPrefix) {
                $user = Auth::user();

                if ($isPallet) {
                    $palletCode = $matchedPallet;
                    $task = ForkliftTask::where('forklift_pallet_code', $palletCode)
                        ->where('forklift_status', 'Pending')
                        ->lockForUpdate()
                        ->first();

                    if (! $task) {
                        $existingTask = ForkliftTask::where('forklift_pallet_code', $palletCode)
                            ->where('forklift_status', 'Progress')
                            ->first();

                        if ($existingTask) {
                            return response()->json([
                                'ok' => false,
                                'message' => 'Task sudah diambil oleh '.$existingTask->forklift_operator.'. Tidak bisa di-scan oleh petugas lain.',
                            ], 422);
                        }

                        return response()->json(['ok' => false, 'message' => 'Task tidak ditemukan untuk pallet ini.'], 422);
                    }

                    $task->update([
                        'forklift_status' => 'Progress',
                        'forklift_operator' => $user->name,
                        'forklift_scan_asal_at' => now(),
                    ]);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Pallet '.$palletCode.' sedang dikerjakan oleh '.$user->name,
                        'task_type' => $task->forklift_type,
                        'next_scan' => 'location',
                        'task_id' => $task->forklift_id,
                    ]);
                }

                if ($isLocation) {
                    // Location codes (LOC-xx) start with "L", so try raw then prefix-stripped.
                    $stripped = ($locationPrefix && str_starts_with($code, $locationPrefix)) ? substr($code, strlen($locationPrefix)) : $code;
                    $lokasi = Lokasi::find($code) ?? Lokasi::find($stripped);

                    // Also try with LOC- prefix if not found
                    if (! $lokasi && ! str_starts_with($code, 'LOC-')) {
                        $lokasi = Lokasi::find('LOC-'.$code);
                    }

                    if (! $lokasi) {
                        return response()->json(['ok' => false, 'message' => 'Lokasi tidak ditemukan: '.$code], 422);
                    }
                    $locationCode = $lokasi->lokasi_code;

                    $task = ForkliftTask::where('forklift_status', 'Progress')
                        ->where('forklift_operator', $user->name)
                        ->lockForUpdate()
                        ->first();

                    if (! $task) {
                        return response()->json(['ok' => false, 'message' => 'Tidak ada task Progress untuk Anda. Scan pallet dulu.'], 422);
                    }

                    if ($task->forklift_type === 'putaway') {
                        if ($locationCode !== $task->forklift_lokasi_tujuan) {
                            $expected = $task->lokasiTujuan?->lokasi_nama ?? $task->forklift_lokasi_tujuan;

                            return response()->json(['ok' => false, 'message' => 'Rak tidak sesuai. Harus scan "'.$expected.'".'], 422);
                        }

                        Stock::where('stock_reff', $task->forklift_pallet_code)
                            ->update([
                                'stock_type' => Stock::TYPE_IN,
                                'stock_code_lokasi' => $locationCode,
                                'stock_pallet_code' => $task->forklift_pallet_code,
                            ]);
                    } else {
                        // Pick task - lokasi harus staging
                        $category = strtolower($lokasi->lokasi_category ?? '');
                        if ($category !== 'staging') {
                            return response()->json(['ok' => false, 'message' => 'Lokasi bukan staging area (category: '.($lokasi->lokasi_category ?? 'kosong').'). Scan lokasi staging seperti LOC-A, LOC-B, dll.'], 422);
                        }

                        Stock::where('stock_pallet_code', $task->forklift_pallet_code)
                            ->update([
                                'stock_type' => Stock::TYPE_STAGING,
                                'stock_code_lokasi' => $locationCode,
                            ]);
                    }

                    $task->update([
                        'forklift_lokasi_final' => $locationCode,
                        'forklift_status' => 'Done',
                        'forklift_scan_tujuan_at' => now(),
                    ]);

                    // Update masuk_detail status ke complete
                    MasukDetail::where('in_detail_code', $task->forklift_reff)
                        ->update(['in_detail_status' => 'complete']);

                    $this->updatePoStatus($task);

                    return response()->json([
                        'ok' => true,
                        'message' => 'Task '.$task->forklift_type.' selesai! Pallet '.$task->forklift_pallet_code.' di '.$locationCode,
                    ]);
                }

                return response()->json(['ok' => false, 'message' => 'Kode tidak dikenal. Scan kode pallet (P...) atau lokasi (L...).'], 422);
            });
        } catch (\Throwable $th) {
            return response()->json(['ok' => false, 'message' => $th->getMessage()], 500);
        }
    }

    protected function updatePoStatus(ForkliftTask $task): void
    {
        $masukDetail = MasukDetail::where('in_detail_code', $task->forklift_reff)->first();
        if (! $masukDetail) {
            return;
        }

        $poDetail = $masukDetail->poDetail;
        if (! $poDetail) {
            return;
        }

        $po = $poDetail->po;
        if (! $po) {
            return;
        }

        // Cek semua PO detail, apakah masing-masing punya masuk_detail yang complete
        $allPoDetails = $po->details()->get();
        $allDone = $allPoDetails->every(function ($pd) {
            return MasukDetail::where('in_detail_reff', $pd->po_detail_code)
                ->where('in_detail_status', 'complete')
                ->exists();
        });

        if ($allDone && $po->po_status !== Po::STATUS_DONE) {
            $po->update(['po_status' => Po::STATUS_DONE]);
        } elseif (! $allDone && in_array($po->po_status, [Po::STATUS_DONE, Po::STATUS_READY])) {
            $po->update(['po_status' => Po::STATUS_PROCESS]);
        }
    }
}
