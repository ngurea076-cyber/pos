<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSerial extends Model
{
    protected $guarded = [];
    protected $hidden = ['purchase_term'];
    protected function casts(): array { return ['sold_at' => 'datetime']; }
    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function order() { return $this->belongsTo(Order::class); }
    public function reseller() { return $this->belongsTo(Reseller::class); }
    public function takeout() { return $this->belongsTo(ResellerTakeout::class, 'takeout_id'); }
    public function purchaseTerm() { return $this->hasOne(SerialPurchaseTerm::class); }
    public function paymentAllocations() { return $this->hasMany(SupplierPaymentAllocation::class); }
}
