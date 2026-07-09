<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sms extends Model
{
    protected $table = 'sms';

    protected $fillable = [
        'text',
        'to',
        'from',
        'response',
        'status',
    ];

    protected $casts = [
        'to' => 'array',
        'response' => 'array',
    ];
}