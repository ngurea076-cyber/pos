<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];
    protected $hidden = ['buying_price'];
    protected function casts(): array { return ['buying_price' => 'decimal:2', 'selling_price' => 'decimal:2', 'is_active' => 'boolean']; }
    public function serials() { return $this->hasMany(ProductSerial::class); }
    public function availableSerials() { return $this->serials()->where('status', 'in_stock'); }
}
