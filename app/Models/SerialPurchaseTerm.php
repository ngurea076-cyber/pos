<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SerialPurchaseTerm extends Model
{
    protected $guarded = [];
    protected $hidden = ['buying_price', 'due_date', 'notes', 'supplier_id', 'set_by'];
    protected function casts(): array { return ['buying_price'=>'decimal:2', 'due_date'=>'date']; }
    public function serial() { return $this->belongsTo(ProductSerial::class, 'product_serial_id'); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function setter() { return $this->belongsTo(User::class, 'set_by'); }
}
