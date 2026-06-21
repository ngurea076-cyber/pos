<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Supplier extends Model {
    protected $guarded = [];
    public function purchaseTerms() { return $this->hasMany(SerialPurchaseTerm::class); }
    public function payments() { return $this->hasMany(SupplierPayment::class); }
}
