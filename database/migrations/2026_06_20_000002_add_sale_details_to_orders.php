<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('customer_source', ['walkin', 'instagram', 'tiktok', 'returning'])->default('walkin')->after('customer_phone');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_serial_id')->nullable()->after('product_id')->constrained('product_serials')->nullOnDelete();
            $table->string('serial_snapshot')->nullable()->after('name_snapshot');
            $table->unsignedSmallInteger('warranty_months')->default(0)->after('serial_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_serial_id');
            $table->dropColumn(['serial_snapshot', 'warranty_months']);
        });
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('customer_source'));
    }
};
