<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropUnique('product_serials_product_id_serial_unique');
            $table->unique('serial');
        });

        foreach (['orders', 'expenses', 'inventory_records', 'supplier_payments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedTinyInteger('edit_count')->default(0);
                $table->boolean('is_invalid')->default(false)->index();
                $table->text('invalid_reason')->nullable();
                $table->foreignId('invalidated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('invalidated_at')->nullable();
            });
        }

        Schema::create('record_corrections', function (Blueprint $table) {
            $table->id();
            $table->string('record_type');
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['edited', 'invalidated']);
            $table->json('before_data');
            $table->json('after_data')->nullable();
            $table->text('reason');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['record_type', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_corrections');
        foreach (['orders', 'expenses', 'inventory_records', 'supplier_payments'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->dropConstrainedForeignId('invalidated_by');
                $table->dropIndex([$table->getTable().'_is_invalid_index']);
                $table->dropColumn(['edit_count', 'is_invalid', 'invalid_reason', 'invalidated_at']);
            });
        }
        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropUnique('product_serials_serial_unique');
            $table->unique(['product_id', 'serial']);
        });
    }
};
