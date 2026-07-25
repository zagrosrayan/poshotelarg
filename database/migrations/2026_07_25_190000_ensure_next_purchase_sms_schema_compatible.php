<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent repair/ensure migration for next-purchase discount + SMS.
 * Safe on MySQL and SQL Server. Does not touch frontend.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureNextPurchaseDiscountsColumns();
        $this->ensureDiscountsColumns();
        $this->ensureDiscountSmsDeliveriesTable();
        $this->healNextPurchaseSettingsData();
        $this->healIssuedNextPurchaseDiscountCenters();
        $this->assertRequiredSchema();
    }

    public function down(): void
    {
        // Non-destructive repair migration — nothing to reverse.
    }

    protected function ensureNextPurchaseDiscountsColumns(): void
    {
        if (!Schema::hasTable('next_purchase_discounts')) {
            Schema::create('next_purchase_discounts', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('code')->nullable()->unique();
                $table->decimal('minimum_purchase_amount', 15, 2)->default(0);
                $table->decimal('discount_percentage', 5, 2)->default(0);
                $table->decimal('apply_on_orders_above', 15, 2)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('sms_enabled')->default(true);
                $table->integer('usage_limit')->nullable();
                $table->integer('usage_count')->default(0);
                $table->integer('days')->nullable();
                $table->integer('discount_validity_days')->nullable();
                $table->json('profit_manager_ids')->nullable();
                $table->json('target_customer_types')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('next_purchase_discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('next_purchase_discounts', 'code')) {
                $table->string('code')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
                $table->boolean('sms_enabled')->default(true);
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'days')) {
                $table->integer('days')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'discount_validity_days')) {
                $table->integer('discount_validity_days')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'profit_manager_ids')) {
                $table->json('profit_manager_ids')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'target_customer_types')) {
                $table->json('target_customer_types')->nullable();
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'minimum_purchase_amount')) {
                $table->decimal('minimum_purchase_amount', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('next_purchase_discounts', 'discount_percentage')) {
                $table->decimal('discount_percentage', 5, 2)->default(0);
            }
        });
    }

    protected function ensureDiscountsColumns(): void
    {
        if (!Schema::hasTable('discounts')) {
            throw new RuntimeException('Table discounts is required but missing.');
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'scope')) {
                $table->string('scope')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'discount_type')) {
                $table->string('discount_type')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'profit_manager_ids')) {
                $table->json('profit_manager_ids')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'customer_id')) {
                $table->unsignedBigInteger('customer_id')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'reserve_number')) {
                $table->string('reserve_number')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'usage_limit')) {
                $table->integer('usage_limit')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'usage_count')) {
                $table->integer('usage_count')->default(0);
            }
            if (!Schema::hasColumn('discounts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'starts_at')) {
                $table->timestamp('starts_at')->nullable();
            }
            if (!Schema::hasColumn('discounts', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
        });
    }

    protected function ensureDiscountSmsDeliveriesTable(): void
    {
        if (!Schema::hasTable('discount_sms_deliveries')) {
            Schema::create('discount_sms_deliveries', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('discount_id');
                $table->string('type', 32);
                $table->unsignedInteger('body_id');
                $table->string('recipient', 20);
                $table->string('recipient_name')->nullable();
                $table->date('scheduled_for');
                $table->string('status', 20)->default('pending');
                $table->unsignedTinyInteger('attempts')->default(0);
                $table->string('provider_reference')->nullable();
                // json() on SQL Server becomes nvarchar(max) — correct for encoded JSON text
                $table->json('last_response')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['discount_id', 'type'], 'discount_sms_deliveries_discount_type_unique');
                $table->index(['status', 'scheduled_for', 'type'], 'discount_sms_deliveries_status_sched_type_idx');
            });

            try {
                Schema::table('discount_sms_deliveries', function (Blueprint $table) {
                    $table->foreign('discount_id', 'discount_sms_deliveries_discount_id_fk')
                        ->references('id')
                        ->on('discounts')
                        ->cascadeOnDelete();
                });
            } catch (Throwable $e) {
                // FK may already exist / unsupported naming — table itself is enough.
            }

            return;
        }

        Schema::table('discount_sms_deliveries', function (Blueprint $table) {
            if (!Schema::hasColumn('discount_sms_deliveries', 'discount_id')) {
                $table->unsignedBigInteger('discount_id');
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'type')) {
                $table->string('type', 32);
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'body_id')) {
                $table->unsignedInteger('body_id')->default(0);
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'recipient')) {
                $table->string('recipient', 20);
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'recipient_name')) {
                $table->string('recipient_name')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'scheduled_for')) {
                $table->date('scheduled_for')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'status')) {
                $table->string('status', 20)->default('pending');
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0);
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'provider_reference')) {
                $table->string('provider_reference')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'last_response')) {
                $table->json('last_response')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'sent_at')) {
                $table->timestamp('sent_at')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            if (!Schema::hasColumn('discount_sms_deliveries', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    protected function healNextPurchaseSettingsData(): void
    {
        if (!Schema::hasTable('next_purchase_discounts')) {
            return;
        }

        // sms_enabled must never be null for active rows
        if (Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
            DB::table('next_purchase_discounts')
                ->whereNull('sms_enabled')
                ->update(['sms_enabled' => true]);
        }

        if (!Schema::hasColumn('next_purchase_discounts', 'profit_manager_ids')) {
            return;
        }

        $allPmIds = Schema::hasTable('profit_managers')
            ? DB::table('profit_managers')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->values()->all()
            : [];

        $settings = DB::table('next_purchase_discounts')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get();

        foreach ($settings as $setting) {
            $pmIds = $this->decodeJsonArray($setting->profit_manager_ids ?? null);
            $targets = $this->decodeJsonArray($setting->target_customer_types ?? null);

            $changed = false;

            // If centers missing/empty, attach all profit managers so minibar/reception works
            if ($pmIds === [] && $allPmIds !== []) {
                $pmIds = $allPmIds;
                $changed = true;
            } else {
                $normalized = array_values(array_unique(array_map('intval', $pmIds)));
                if ($normalized !== $pmIds) {
                    $pmIds = $normalized;
                    $changed = true;
                }
            }

            if ($targets === []) {
                $targets = ['resident', 'Non_resident'];
                $changed = true;
            }

            $validity = $setting->discount_validity_days ?? null;
            if (($validity === null || (int) $validity <= 0) && Schema::hasColumn('next_purchase_discounts', 'discount_validity_days')) {
                $validity = (int) ($setting->days ?? 10);
                if ($validity <= 0) {
                    $validity = 10;
                }
                $changed = true;
            }

            if (!$changed) {
                continue;
            }

            $payload = [
                'profit_manager_ids' => json_encode(array_values($pmIds)),
                'target_customer_types' => json_encode(array_values($targets)),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('next_purchase_discounts', 'discount_validity_days')) {
                $payload['discount_validity_days'] = $validity;
            }

            DB::table('next_purchase_discounts')->where('id', $setting->id)->update($payload);
        }
    }

    protected function healIssuedNextPurchaseDiscountCenters(): void
    {
        if (!Schema::hasTable('discounts') || !Schema::hasColumn('discounts', 'profit_manager_ids')) {
            return;
        }

        $settings = DB::table('next_purchase_discounts')
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$settings) {
            return;
        }

        $settingsPmIds = $this->decodeJsonArray($settings->profit_manager_ids ?? null);
        if ($settingsPmIds === []) {
            return;
        }

        $settingsPmIds = array_values(array_unique(array_map('intval', $settingsPmIds)));
        $encoded = json_encode($settingsPmIds);

        // Active unused next_purchase codes inherit current settings centers
        $query = DB::table('discounts')
            ->where('scope', 'next_purchase')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        if (Schema::hasColumn('discounts', 'usage_limit') && Schema::hasColumn('discounts', 'usage_count')) {
            $query->whereColumn('usage_count', '<', 'usage_limit');
        }

        $query->update([
            'profit_manager_ids' => $encoded,
            'updated_at' => now(),
        ]);
    }

    protected function assertRequiredSchema(): void
    {
        $required = [
            'next_purchase_discounts' => [
                'id', 'name', 'minimum_purchase_amount', 'discount_percentage',
                'is_active', 'sms_enabled', 'discount_validity_days',
                'profit_manager_ids', 'target_customer_types',
            ],
            'discount_sms_deliveries' => [
                'id', 'discount_id', 'type', 'body_id', 'recipient',
                'scheduled_for', 'status', 'attempts', 'last_response', 'sent_at',
            ],
            'discounts' => [
                'id', 'code', 'discount_value', 'discount_type', 'scope',
                'profit_manager_ids', 'customer_id', 'is_active',
            ],
        ];

        $missing = [];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                $missing[] = "table:{$table}";
                continue;
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $missing[] = "{$table}.{$column}";
                }
            }
        }

        if ($missing !== []) {
            throw new RuntimeException(
                'next-purchase SMS schema still incomplete after ensure migration: ' . implode(', ', $missing)
            );
        }
    }

    protected function decodeJsonArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return array_values($value);
        }

        if (is_object($value)) {
            return array_values((array) $value);
        }

        if (!is_string($value)) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
};
