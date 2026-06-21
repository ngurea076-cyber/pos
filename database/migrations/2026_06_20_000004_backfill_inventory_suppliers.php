<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement('UPDATE inventory_records ir JOIN product_serials ps ON ps.id = ir.product_serial_id SET ir.supplier_id = ps.supplier_id WHERE ir.supplier_id IS NULL AND ps.supplier_id IS NOT NULL');
        DB::statement('UPDATE inventory_records ir JOIN stock_intakes si ON ir.reference = CONCAT("INTAKE-", si.id) SET ir.supplier_id = si.supplier_id WHERE ir.supplier_id IS NULL AND si.supplier_id IS NOT NULL');

        if (DB::table('inventory_records')->whereNull('supplier_id')->exists() || DB::table('product_serials')->whereNull('supplier_id')->exists()) {
            $supplierId = DB::table('suppliers')->where('name', 'Unspecified supplier')->value('id');
            if (! $supplierId) {
                $supplierId = DB::table('suppliers')->insertGetId(['name'=>'Unspecified supplier','created_at'=>now(),'updated_at'=>now()]);
            }
            DB::table('product_serials')->whereNull('supplier_id')->update(['supplier_id'=>$supplierId]);
            DB::table('stock_intakes')->whereNull('supplier_id')->update(['supplier_id'=>$supplierId]);
            DB::table('inventory_records')->whereNull('supplier_id')->update(['supplier_id'=>$supplierId]);
        }
    }

    public function down(): void {}
};
