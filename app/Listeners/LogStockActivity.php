<?php

namespace App\Listeners;

use App\Events\StockCreated;
use App\Events\StockDeleted;
use App\Events\StockUpdated;
use App\Models\StockLog;

class LogStockActivity
{
    public function handleCreated(StockCreated $event): void
    {
        $stock = $event->stock;

        StockLog::create([
            'stock_log_code'    => StockLog::generateCode(),
            'stock_id'          => $stock->stock_id,
            'stock_code'        => $stock->stock_code,
            'stock_id_product'  => $stock->stock_id_product,
            'stock_code_lokasi' => $stock->stock_code_lokasi,
            'stock_type'        => $stock->stock_type,
            'stock_qty'         => $stock->stock_qty,
            'stock_qty_before'  => null,
            'stock_qty_after'   => $stock->stock_qty,
            'action'            => 'CREATE',
            'description'       => 'Stock baru dibuat',
            'stock_reff'        => $stock->stock_reff,
            'created_at'        => now(),
        ]);
    }

    public function handleUpdated(StockUpdated $event): void
    {
        $stock = $event->stock;
        $original = $event->original;

        $dirty = $stock->getDirty();
        $changes = [];

        foreach ($dirty as $key => $newVal) {
            if ($key === 'updated_at') continue;
            $oldVal = $original[$key] ?? null;
            if ((string) $oldVal !== (string) $newVal) {
                $changes[$key] = ['old' => $oldVal, 'new' => $newVal];
            }
        }

        if (empty($changes)) return;

        $before = $original['stock_qty'] ?? null;
        $after = $stock->stock_qty;

        $descParts = [];
        foreach ($changes as $key => $change) {
            $descParts[] = $key.': '.($change['old'] ?? 'null').' → '.($change['new'] ?? 'null');
        }

        // Determine action based on what changed
        $action = 'UPDATE';
        if (isset($changes['stock_qty'])) {
            $diff = (float) ($changes['stock_qty']['new'] ?? 0) - (float) ($changes['stock_qty']['old'] ?? 0);
            $action = $diff > 0 ? 'INCREASE' : 'DECREASE';
        } elseif (isset($changes['stock_type'])) {
            $action = 'TYPE_CHANGE';
        } elseif (isset($changes['stock_code_lokasi'])) {
            $action = 'RELOCATION';
        }

        StockLog::create([
            'stock_log_code'    => StockLog::generateCode(),
            'stock_id'          => $stock->stock_id,
            'stock_code'        => $stock->stock_code,
            'stock_id_product'  => $stock->stock_id_product,
            'stock_code_lokasi' => $stock->stock_code_lokasi,
            'stock_type'        => $stock->stock_type,
            'stock_qty'         => $stock->stock_qty,
            'stock_qty_before'  => $before,
            'stock_qty_after'   => $after,
            'action'            => $action,
            'description'       => implode('; ', $descParts),
            'stock_reff'        => $stock->stock_reff,
            'created_at'        => now(),
        ]);
    }

    public function handleDeleted(StockDeleted $event): void
    {
        $stock = $event->stock;

        StockLog::create([
            'stock_log_code'    => StockLog::generateCode(),
            'stock_id'          => $stock->stock_id,
            'stock_code'        => $stock->stock_code,
            'stock_id_product'  => $stock->stock_id_product,
            'stock_code_lokasi' => $stock->stock_code_lokasi,
            'stock_type'        => $stock->stock_type,
            'stock_qty'         => $stock->stock_qty,
            'stock_qty_before'  => $stock->stock_qty,
            'stock_qty_after'   => 0,
            'action'            => 'DELETE',
            'description'       => 'Stock dihapus',
            'stock_reff'        => $stock->stock_reff,
            'created_at'        => now(),
        ]);
    }
}
