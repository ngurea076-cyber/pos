<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StockIntake extends Model { protected $guarded = []; public function product(){return $this->belongsTo(Product::class);} public function supplier(){return $this->belongsTo(Supplier::class);} }
