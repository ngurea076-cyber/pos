<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE inventory_records MODIFY type ENUM('stock_intake','reseller_takeout','wholesaler_return','customer_return','sale') NOT NULL");
        $fallback=DB::table('suppliers')->where('name','Unspecified supplier')->value('id');
        foreach(DB::table('product_serials')->whereNotNull('order_id')->whereNotNull('sold_at')->get() as $serial){
            DB::table('inventory_records')->updateOrInsert(
                ['reference'=>'SALE-'.$serial->order_id.'-'.$serial->id],
                ['type'=>'sale','product_id'=>$serial->product_id,'product_serial_id'=>$serial->id,'supplier_id'=>$serial->supplier_id?:$fallback,'quantity'=>-1,'notes'=>null,'created_by'=>DB::table('orders')->where('id',$serial->order_id)->value('attendant_id'),'happened_at'=>$serial->sold_at,'created_at'=>now(),'updated_at'=>now()]
            );
        }
    }
    public function down(): void { DB::table('inventory_records')->where('type','sale')->delete(); DB::statement("ALTER TABLE inventory_records MODIFY type ENUM('stock_intake','reseller_takeout','wholesaler_return','customer_return') NOT NULL"); }
};
