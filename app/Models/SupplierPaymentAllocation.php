<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPaymentAllocation extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['amount'=>'decimal:2']; }
    public function payment() { return $this->belongsTo(SupplierPayment::class, 'supplier_payment_id'); }
    public function serial() { return $this->belongsTo(ProductSerial::class, 'product_serial_id'); }
}
