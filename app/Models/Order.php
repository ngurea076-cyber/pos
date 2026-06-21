<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['is_invalid'=>'boolean', 'invalidated_at'=>'datetime']; }
    protected static function booted(): void { static::creating(fn (Order $order) => $order->order_number ??= 'ORD-'.now()->format('ymd').'-'.strtoupper(substr((string) \Illuminate\Support\Str::ulid(), -6))); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function attendant() { return $this->belongsTo(User::class, 'attendant_id'); }
    public function corrections() { return $this->morphMany(RecordCorrection::class, 'record', 'record_type', 'record_id'); }
}
