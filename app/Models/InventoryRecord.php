<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryRecord extends Model {
    protected $guarded=[];
    protected function casts():array{return ['happened_at'=>'datetime','is_invalid'=>'boolean','invalidated_at'=>'datetime'];}
    public function product(){return $this->belongsTo(Product::class);}
    public function serial(){return $this->belongsTo(ProductSerial::class,'product_serial_id');}
    public function supplier(){return $this->belongsTo(Supplier::class);}
    public function reseller(){return $this->belongsTo(Reseller::class);}
    public function user(){return $this->belongsTo(User::class,'created_by');}
    public function corrections(){return $this->morphMany(RecordCorrection::class,'record','record_type','record_id');}
}
