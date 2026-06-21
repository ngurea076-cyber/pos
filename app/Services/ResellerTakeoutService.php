<?php

namespace App\Services;

use App\Models\InventoryRecord;
use App\Models\Product;
use App\Models\ProductSerial;
use Illuminate\Support\Facades\DB;

class ResellerTakeoutService
{
    public function returnToStock(int $serialId, ?int $userId): ProductSerial
    {
        return DB::transaction(function () use ($serialId, $userId) {
            $serial = ProductSerial::whereKey($serialId)->where('status', 'with_reseller')->lockForUpdate()->firstOrFail();
            $resellerId = $serial->reseller_id;
            $takeoutId = $serial->takeout_id;

            $serial->update(['status' => 'in_stock', 'reseller_id' => null, 'takeout_id' => null]);
            Product::whereKey($serial->product_id)->increment('stock');
            InventoryRecord::create([
                'type' => 'reseller_return',
                'product_id' => $serial->product_id,
                'product_serial_id' => $serial->id,
                'supplier_id' => $serial->supplier_id,
                'reseller_id' => $resellerId,
                'quantity' => 1,
                'reference' => 'RESELLER-RETURN-'.$takeoutId.'-'.$serial->id.'-'.now()->format('YmdHis'),
                'created_by' => $userId,
                'happened_at' => now(),
            ]);

            return $serial->refresh();
        });
    }
}
