<?php

namespace App\Console\Commands;

use App\Services\StockReconciliationService;
use Illuminate\Console\Command;

class ReconcileStock extends Command
{
    protected $signature = 'shop:reconcile-stock {--fix : Update product stock counters}';
    protected $description = 'Compare product stock counters with serialized units';

    public function handle(StockReconciliationService $service): int
    {
        $rows = $service->mismatches();
        if (! $rows) { $this->info('Stock is reconciled.'); return self::SUCCESS; }
        $this->table(['ID','Product','Stored','Calculated'], array_map(fn ($row)=>array_values($row), $rows));
        if ($this->option('fix')) { $this->info($service->repair().' product stock counter(s) repaired.'); return self::SUCCESS; }
        $this->error('Stock mismatches found. Run with --fix to repair them.');
        return self::FAILURE;
    }
}
