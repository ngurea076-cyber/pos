<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Quotation extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date', 'subtotal' => 'decimal:2', 'discount' => 'decimal:2', 'vat_amount' => 'decimal:2', 'total' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::creating(function (Quotation $quotation) {
            $quotation->quotation_number ??= 'QT-'.now()->format('ymd').'-'.strtoupper(substr((string) Str::ulid(), -6));
        });
    }

    public function items() { return $this->hasMany(QuotationItem::class); }
    public function attendant() { return $this->belongsTo(User::class, 'served_by'); }
    public function order() { return $this->belongsTo(Order::class); }

    public function refreshExpiryStatus(): void
    {
        if ($this->expires_at?->isBefore(today()) && ! in_array($this->status, ['accepted', 'converted', 'rejected'], true)) {
            $this->update(['status' => 'expired']);
        }
    }
}
