<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ResellerTakeout extends Model { protected $guarded = []; public function reseller(){return $this->belongsTo(Reseller::class);} public function serials(){return $this->hasMany(ProductSerial::class, 'takeout_id');} }
