<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountSmsDelivery extends Model
{
    public const TYPE_ISSUED = 'issued';
    public const TYPE_REMINDER = 'reminder';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'discount_id',
        'type',
        'body_id',
        'recipient',
        'recipient_name',
        'scheduled_for',
        'status',
        'attempts',
        'provider_reference',
        'last_response',
        'sent_at',
    ];

    protected $casts = [
        'scheduled_for' => 'date',
        'last_response' => 'array',
        'sent_at' => 'datetime',
        'attempts' => 'integer',
        'body_id' => 'integer',
    ];

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }
}
