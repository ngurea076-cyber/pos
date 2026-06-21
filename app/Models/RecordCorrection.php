<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecordCorrection extends Model
{
    protected $guarded = [];
    protected function casts(): array { return ['before_data'=>'array', 'after_data'=>'array']; }
    public function user() { return $this->belongsTo(User::class); }
}
