<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierPayment extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['amount'=>'decimal:2', 'paid_at'=>'datetime', 'is_invalid'=>'boolean', 'invalidated_at'=>'datetime']; }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function recorder() { return $this->belongsTo(User::class, 'recorded_by'); }
    public function allocations() { return $this->hasMany(SupplierPaymentAllocation::class); }
    public function corrections() { return $this->morphMany(RecordCorrection::class, 'record', 'record_type', 'record_id'); }
}
