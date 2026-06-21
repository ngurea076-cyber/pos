<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'is_invalid'=>'boolean', 'invalidated_at'=>'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function corrections() { return $this->morphMany(RecordCorrection::class, 'record', 'record_type', 'record_id'); }
}
