<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitManager extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function status()
    {
        return $this->belongsTo(Type::class,'status');
    }
}
