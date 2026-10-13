<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['unit_price' => 'decimal:2', 'amount' => 'decimal:2']; }
    public function product() { return $this->belongsTo(Product::class); }
}
