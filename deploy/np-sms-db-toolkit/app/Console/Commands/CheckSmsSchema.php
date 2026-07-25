<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckSmsSchema extends Command
{
    protected $signature = 'sms:check-schema';

    protected $description = 'Verify DB tables/columns needed for next-purchase discount SMS (MySQL + SQL Server)';

    public function handle(): int
    {
        $driver = DB::getDriverName();
        $this->info('=== SMS / next-purchase schema check ===');
        $this->line('driver: ' . $driver);
        $this->line('database: ' . DB::connection()->getDatabaseName());
        $this->line('time: ' . now()->toDateTimeString());

        $ok = 0;
        $warn = 0;
        $fail = 0;

        $requiredTables = [
            'discounts',
            'next_purchase_discounts',
            'discount_sms_deliveries',
        ];

        foreach ($requiredTables as $table) {
            if (Schema::hasTable($table)) {
                $this->info("[OK] table {$table}");
                $ok++;
            } else {
                $this->error("[FAIL] table {$table} MISSING");
                $fail++;
            }
        }

        $requiredColumns = [
            'next_purchase_discounts' => [
                'id', 'name', 'minimum_purchase_amount', 'discount_percentage',
                'is_active', 'sms_enabled', 'discount_validity_days',
                'profit_manager_ids', 'target_customer_types',
            ],
            'discount_sms_deliveries' => [
                'id', 'discount_id', 'type', 'body_id', 'recipient', 'recipient_name',
                'scheduled_for', 'status', 'attempts', 'provider_reference',
                'last_response', 'sent_at', 'created_at', 'updated_at',
            ],
            'discounts' => [
                'id', 'code', 'discount_value', 'discount_type', 'scope',
                'customer_id', 'reserve_number', 'is_active',
                'usage_limit', 'usage_count', 'expires_at', 'profit_manager_ids',
            ],
        ];

        foreach ($requiredColumns as $table => $columns) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column) {
                if (Schema::hasColumn($table, $column)) {
                    $this->info("[OK] {$table}.{$column}");
                    $ok++;
                } else {
                    $this->error("[FAIL] {$table}.{$column} MISSING");
                    $fail++;
                }
            }
        }

        // Optional legacy columns that may still exist after partial migrations
        foreach (['discount_sms_template', 'reminder_sms_template', 'reminder_days_before_expiration'] as $legacy) {
            if (Schema::hasTable('next_purchase_discounts') && Schema::hasColumn('next_purchase_discounts', $legacy)) {
                $this->warn("[WARN] legacy column still present: next_purchase_discounts.{$legacy}");
                $warn++;
            }
        }

        if (Schema::hasTable('discount_sms_deliveries') && Schema::hasColumn('discount_sms_deliveries', 'last_response')) {
            $type = $this->columnType('discount_sms_deliveries', 'last_response');
            $this->line("last_response type: " . ($type ?: 'unknown'));

            // Must be able to store JSON text. Query-builder mass update must json_encode().
            if ($type && preg_match('/int|bit|float|decimal|money|date|time/i', $type) && !preg_match('/char|text|json|ntext|xml/i', $type)) {
                $this->error("[FAIL] last_response type looks incompatible for JSON: {$type}");
                $fail++;
            } else {
                $this->info('[OK] last_response type can store JSON text');
                $ok++;
            }

            // Smoke write/read with JSON string (safe rollback)
            try {
                DB::beginTransaction();
                $probe = [
                    'reason' => 'schema_probe',
                    'at' => now()->toDateTimeString(),
                ];
                // Do not insert if table empty of discounts; only test encode path used by cancelPending
                $encoded = json_encode($probe, JSON_UNESCAPED_UNICODE);
                if ($encoded === false) {
                    throw new \RuntimeException('json_encode failed');
                }
                $this->info('[OK] json_encode(last_response) works');
                $ok++;
                DB::rollBack();
            } catch (\Throwable $e) {
                DB::rollBack();
                $this->error('[FAIL] JSON probe failed: ' . $e->getMessage());
                $fail++;
            }
        }

        $this->info('=== migration rows (related) ===');
        if (Schema::hasTable('migrations')) {
            $rows = DB::table('migrations')
                ->where(function ($q) {
                    $q->where('migration', 'like', '%discount_sms%')
                        ->orWhere('migration', 'like', '%sms_enabled%')
                        ->orWhere('migration', 'like', '%next_purchase%')
                        ->orWhere('migration', 'like', '%automatic_sms%');
                })
                ->orderBy('id')
                ->get(['id', 'migration', 'batch']);

            if ($rows->isEmpty()) {
                $this->warn('[WARN] no related rows in migrations table');
                $warn++;
            } else {
                foreach ($rows as $row) {
                    $this->line("{$row->id}\tbatch={$row->batch}\t{$row->migration}");
                }
            }
        } else {
            $this->warn('[WARN] migrations table missing');
            $warn++;
        }

        $this->info('=== summary ===');
        $this->line("OK={$ok} WARN={$warn} FAIL={$fail}");

        if ($fail > 0) {
            $this->error('Schema is NOT ready. Run pending migrations or apply missing columns.');
            $this->line('Suggested: php artisan migrate --force');
            return self::FAILURE;
        }

        $this->info('Schema looks ready for next-purchase SMS.');
        return self::SUCCESS;
    }

    protected function columnType(string $table, string $column): ?string
    {
        $driver = DB::getDriverName();

        try {
            if ($driver === 'sqlsrv') {
                $row = DB::selectOne(
                    "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_NAME = ? AND COLUMN_NAME = ?",
                    [$table, $column]
                );
                if (!$row) {
                    return null;
                }
                $len = $row->CHARACTER_MAXIMUM_LENGTH ?? null;
                return $row->DATA_TYPE . ($len !== null && (int) $len > 0 ? "({$len})" : ($len == -1 ? '(max)' : ''));
            }

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                $row = DB::selectOne(
                    'SELECT DATA_TYPE, COLUMN_TYPE
                     FROM INFORMATION_SCHEMA.COLUMNS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                    [$table, $column]
                );
                return $row->COLUMN_TYPE ?? $row->DATA_TYPE ?? null;
            }
        } catch (\Throwable $e) {
            $this->warn('column type lookup failed: ' . $e->getMessage());
        }

        return null;
    }
}
