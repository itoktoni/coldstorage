<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Keluar;
use App\Models\KeluarDetail;
use App\Models\KeluarRealisasi;
use App\Models\SoPrepare;

$detail = KeluarDetail::with(['keluar', 'soDetail.so'])->findOrFail(5);

echo "=== Detail 5 ===\n";
echo "product: {$detail->out_detail_id_product}\n";
echo "qty_needed: {$detail->out_detail_qty}\n";
echo "keluar_code: {$detail->out_detail_code_keluar}\n";
echo "so_code: " . ($detail->soDetail?->so?->so_code ?? 'none') . "\n";
$soSt = $detail->soDetail?->so?->so_status;
echo "so_status: " . ($soSt instanceof \BackedEnum ? $soSt->value : ($soSt ?? 'none')) . "\n";
echo "keluar_status: " . ($detail->keluar?->out_status ?? 'none') . "\n";

$picked = KeluarRealisasi::where('out_realisasi_id_detail', 5)->sum('out_realisasi_qty');
echo "total_picked: {$picked}\n";

$allDetails = KeluarDetail::where('out_detail_code_keluar', $detail->out_detail_code_keluar)->get();
echo "\n=== All details for keluar {$detail->out_detail_code_keluar} ===\n";
foreach ($allDetails as $d) {
    $dp = KeluarRealisasi::where('out_realisasi_id_detail', $d->out_detail_id)->sum('out_realisasi_qty');
    echo "detail {$d->out_detail_id}: product={$d->out_detail_id_product} qty={$d->out_detail_qty} picked={$dp}\n";
}

$so = $detail->soDetail?->so;
if ($so) {
    echo "\n=== SO: {$so->so_code} (status: {$so->so_status}) ===\n";
    
    // All keluar codes for this SO
    $keluarCodes = KeluarDetail::whereHas('soDetail', fn ($q) => $q->where('so_detail_id_so', $so->so_id))
        ->pluck('out_detail_code_keluar')
        ->unique();
    echo "keluar_codes: " . $keluarCodes->implode(', ') . "\n";
    
    foreach ($keluarCodes as $code) {
        $status = Keluar::where('out_code', $code)->value('out_status');
        echo "  keluar {$code} status: {$status}\n";
    }
    
    $prepare = SoPrepare::where('so_prepare_id_so', $so->so_id)->first();
    echo "prepare_exists: " . ($prepare ? 'yes' : 'no') . "\n";
    if ($prepare) {
        echo "prepare_status: {$prepare->so_prepare_status}\n";
    }
}
