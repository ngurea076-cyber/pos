<?php

namespace App\Console\Commands;

use App\Services\StockReconciliationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProductionHealth extends Command
{
    protected $signature = 'shop:health';
    protected $description = 'Check production configuration, database, storage and inventory integrity';

    public function handle(StockReconciliationService $stocks): int
    {
        $checks = [];
        try { DB::select('SELECT 1'); $checks['Database']='OK'; } catch (\Throwable $e) { $checks['Database']='FAILED: '.$e->getMessage(); }
        $checks['Application environment'] = app()->environment('production') ? 'OK' : 'FAILED: APP_ENV is not production';
        $checks['Debug mode'] = config('app.debug') ? 'FAILED: APP_DEBUG is enabled' : 'OK';
        $checks['HTTPS URL'] = str_starts_with(config('app.url'), 'https://') ? 'OK' : 'FAILED: APP_URL is not HTTPS';
        $checks['Storage writable'] = is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')) ? 'OK' : 'FAILED';
        $checks['Stock integrity'] = count($stocks->mismatches()) ? 'FAILED: stock reconciliation required' : 'OK';
        $checks['Serial suppliers'] = DB::table('product_serials')->whereNull('supplier_id')->exists() ? 'FAILED: missing suppliers' : 'OK';
        $checks['Inventory suppliers'] = DB::table('inventory_records')->whereNull('supplier_id')->exists() ? 'FAILED: missing suppliers' : 'OK';
        $this->table(['Check','Result'], collect($checks)->map(fn ($value,$key)=>[$key,$value])->values()->all());
        return collect($checks)->contains(fn ($value)=>str_starts_with($value,'FAILED')) ? self::FAILURE : self::SUCCESS;
    }
}
