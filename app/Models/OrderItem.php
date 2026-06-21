<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model { protected $guarded = []; protected $hidden = ['buying_price_snapshot']; public $timestamps = false; public function product() { return $this->belongsTo(Product::class); } public function serial() { return $this->belongsTo(ProductSerial::class, 'product_serial_id'); } }
