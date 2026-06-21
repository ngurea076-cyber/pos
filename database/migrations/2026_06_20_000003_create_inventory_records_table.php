<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_records', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['stock_intake', 'reseller_takeout', 'wholesaler_return', 'customer_return']);
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reseller_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->string('reference')->nullable()->unique();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('happened_at');
            $table->timestamps();
            $table->index(['type', 'happened_at']);
        });

        foreach (DB::table('stock_intakes')->get() as $intake) {
            DB::table('inventory_records')->insert(['type'=>'stock_intake','product_id'=>$intake->product_id,'supplier_id'=>$intake->supplier_id,'quantity'=>$intake->quantity,'reference'=>'INTAKE-'.$intake->id,'notes'=>$intake->notes,'created_by'=>$intake->created_by,'happened_at'=>$intake->created_at,'created_at'=>now(),'updated_at'=>now()]);
        }
        foreach (DB::table('product_serials')->whereNotNull('takeout_id')->get() as $serial) {
            DB::table('inventory_records')->insert(['type'=>'reseller_takeout','product_id'=>$serial->product_id,'product_serial_id'=>$serial->id,'reseller_id'=>$serial->reseller_id,'quantity'=>-1,'reference'=>'TAKEOUT-'.$serial->takeout_id.'-'.$serial->id,'created_by'=>null,'happened_at'=>$serial->updated_at,'created_at'=>now(),'updated_at'=>now()]);
        }
    }

    public function down(): void { Schema::dropIfExists('inventory_records'); }
};
