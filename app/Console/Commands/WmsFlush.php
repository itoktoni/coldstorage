<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WmsFlush extends Command
{
    protected $signature = 'wms:flush
                            {--all : Flush semua data WMS}
                            {--so : Flush SO + detail + prepare + keluar + stock}
                            {--po : Flush PO + detail}
                            {--stock : Flush stock + log + assignment}
                            {--inbound : Flush masuk detail + realisasi}
                            {--outbound : Flush keluar + detail + realisasi + split}
                            {--master : Flush customer, product, gudang, lokasi (+ semua transaksi)}
                            {--force : Skip konfirmasi}';

    protected $description = 'Kosongkan data WMS (SO, PO, Stock, dll)';

    private array $counts = [];

    public function handle(): int
    {
        $all = $this->option('all');

        // Default: flush semua transaksi (SO, PO, stock, inbound, outbound)
        $flushSo       = $all || $this->option('so') || ! $this->hasAnyFlag();
        $flushPo       = $all || $this->option('po') || ! $this->hasAnyFlag();
        $flushStock    = $all || $this->option('stock') || ! $this->hasAnyFlag();
        $flushInbound  = $all || $this->option('inbound') || ! $this->hasAnyFlag();
        $flushOutbound = $all || $this->option('outbound') || ! $this->hasAnyFlag();

        $tables = [];

        if ($flushOutbound || $flushSo) {
            $tables = array_merge($tables, [
                'stock_log', 'stock_assignment',
                'so_prepare_detail', 'so_prepare',
                'keluar_realisasi', 'keluar_detail', 'keluar',
                'split',
            ]);
        }

        if ($flushStock) {
            $tables[] = 'stock';
        }

        if ($flushSo) {
            $tables = array_merge($tables, ['detail_so', 'so']);
        }

        if ($flushInbound) {
            $tables = array_merge($tables, ['masuk_realisasi', 'masuk_detail']);
        }

        if ($flushPo) {
            $tables = array_merge($tables, ['detail_po', 'po']);
        }

        if ($this->option('master')) {
            $tables = array_merge($tables, [
                'customer', 'product', 'lokasi', 'gudang',
            ]);
        }

        $tables = array_values(array_unique(array_filter(
            $tables,
            fn ($t) => Schema::hasTable($t)
        )));

        if (empty($tables)) {
            $this->error('Tidak ada tabel untuk dikosongkan.');
            return 1;
        }

        // Hitung dulu
        $total = 0;
        foreach ($tables as $table) {
            $this->counts[$table] = $this->tableCount($table);
            $total += $this->counts[$table];
        }

        $this->newLine();
        $this->line('<bg=red;fg=white> PERINGATAN: Ini akan menghapus data secara permanen! </>');
        $this->newLine();
        $this->line('<info>Tabel yang akan dikosongkan:</info>');
        foreach ($this->counts as $table => $count) {
            $this->line("  {$table}: {$count} baris");
        }
        $this->line("  ─────────────────");
        $this->line("  Total: {$total} baris");

        if ($total === 0) {
            $this->newLine();
            $this->info('Semua tabel sudah kosong.');
            return 0;
        }

        if (! $this->option('force') && ! $this->confirm('Yakin ingin menghapus semua data di atas?')) {
            $this->info('Dibatalkan.');
            return 0;
        }

        // Eksekusi
        $this->newLine();
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            if ($this->counts[$table] > 0) {
                DB::table($table)->truncate();
                $this->line("  <info>✓</info> {$table} — {$this->counts[$table]} baris dihapus");
            }
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->newLine();
        $this->info("Selesai! {$total} baris dihapus.");
        return 0;
    }

    private function hasAnyFlag(): bool
    {
        return $this->option('so')
            || $this->option('po')
            || $this->option('stock')
            || $this->option('inbound')
            || $this->option('outbound')
            || $this->option('master')
            || $this->option('all');
    }

    private function tableCount(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }
}
