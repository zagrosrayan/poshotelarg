<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'operation',
        'loggable_type',
        'loggable_id',
        'message',
        'date',
        'ip',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusType()
    {
        return $this->belongsTo(Type::class, 'status');
    }

    public function operationType()
    {
        return $this->belongsTo(Type::class, 'operation');
    }

    public function loggable()
    {
        return $this->morphTo();
    }
}
