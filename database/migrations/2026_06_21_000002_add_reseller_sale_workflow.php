<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY customer_source ENUM('walkin','instagram','tiktok','returning','reseller') NOT NULL DEFAULT 'walkin'");
        DB::statement("ALTER TABLE inventory_records MODIFY type ENUM('stock_intake','reseller_takeout','reseller_return','wholesaler_return','customer_return','sale') NOT NULL");
    }

    public function down(): void
    {
        DB::table('orders')->where('customer_source', 'reseller')->update(['customer_source' => 'returning']);
        DB::table('inventory_records')->where('type', 'reseller_return')->delete();
        DB::statement("ALTER TABLE orders MODIFY customer_source ENUM('walkin','instagram','tiktok','returning') NOT NULL DEFAULT 'walkin'");
        DB::statement("ALTER TABLE inventory_records MODIFY type ENUM('stock_intake','reseller_takeout','wholesaler_return','customer_return','sale') NOT NULL");
    }
};
