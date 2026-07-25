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

    /**
     * Mass-update must JSON-encode last_response itself — query builder skips Eloquent casts
     * (SQL Server throws "Array to string conversion" otherwise).
     */
    public static function cancelPending(string $reason, ?callable $query = null): int
    {
        $builder = static::query()->where('status', self::STATUS_PENDING);
        if ($query) {
            $query($builder);
        }

        return $builder->update([
            'status' => self::STATUS_CANCELLED,
            'last_response' => json_encode([
                'reason' => $reason,
                'at' => now()->toDateTimeString(),
            ], JSON_UNESCAPED_UNICODE),
        ]);
    }
}
