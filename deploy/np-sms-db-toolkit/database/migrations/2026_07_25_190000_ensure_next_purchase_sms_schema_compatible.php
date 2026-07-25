<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SAFE / idempotent schema ensure for next-purchase discount + SMS.
 *
 * Rules:
 * - Never DROP columns/tables
 * - Never CHANGE column types
 * - Never mass-update issued discount codes / business data
 * - Only ADD missing nullable/defaulted columns or create missing delivery table
 * - Compatible with MySQL and SQL Server
 */
return new class extends Migration
{
    public function up(): void
    {
        // DDL is not wrapped in a transaction (MySQL/SQL Server auto-commit DDL).
        $this->ensureCoreTablesExist();
        $this->ensureNextPurchaseDiscountsColumns();
        $this->ensureDiscountsColumns();
        $this->ensureDiscountSmsDeliveriesTable();
        $this->fillNullSmsEnabledOnly();
        $this->assertRequiredSchema();
    }

    public function down(): void
    {
        // Intentional no-op: additive repair migration must not destroy schema/data.
    }

    protected function ensureCoreTablesExist(): void
    {
        foreach (['discounts', 'next_purchase_discounts'] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Critical table [{$table}] is missing. Refusing to invent production schema. Restore from backup first."
                );
            }
        }
    }

    protected function ensureNextPurchaseDiscountsColumns(): void
    {
        // Add only missing columns; no defaults that rewrite existing business rows except sms_enabled null-fill later.
        $adds = [];

        if (!Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
            $adds[] = function (Blueprint $table) {
                $table->boolean('sms_enabled')->default(true);
            };
        }
        if (!Schema::hasColumn('next_purchase_discounts', 'discount_validity_days')) {
            $adds[] = function (Blueprint $table) {
                $table->integer('discount_validity_days')->nullable();
            };
        }
        if (!Schema::hasColumn('next_purchase_discounts', 'days')) {
            $adds[] = function (Blueprint $table) {
                $table->integer('days')->nullable();
            };
        }
        if (!Schema::hasColumn('next_purchase_discounts', 'profit_manager_ids')) {
            $adds[] = function (Blueprint $table) {
                $table->json('profit_manager_ids')->nullable();
            };
        }
        if (!Schema::hasColumn('next_purchase_discounts', 'target_customer_types')) {
            $adds[] = function (Blueprint $table) {
                $table->json('target_customer_types')->nullable();
            };
        }
        if (!Schema::hasColumn('next_purchase_discounts', 'code')) {
            $adds[] = function (Blueprint $table) {
                $table->string('code')->nullable();
            };
        }

        if ($adds === []) {
            return;
        }

        Schema::table('next_purchase_discounts', function (Blueprint $table) use ($adds) {
            foreach ($adds as $add) {
                $add($table);
            }
        });
    }

    protected function ensureDiscountsColumns(): void
    {
        $adds = [];

        if (!Schema::hasColumn('discounts', 'scope')) {
            $adds[] = fn (Blueprint $table) => $table->string('scope')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'discount_type')) {
            $adds[] = fn (Blueprint $table) => $table->string('discount_type')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'profit_manager_ids')) {
            $adds[] = fn (Blueprint $table) => $table->json('profit_manager_ids')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'customer_id')) {
            $adds[] = fn (Blueprint $table) => $table->unsignedBigInteger('customer_id')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'reserve_number')) {
            $adds[] = fn (Blueprint $table) => $table->string('reserve_number')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'usage_limit')) {
            $adds[] = fn (Blueprint $table) => $table->integer('usage_limit')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'usage_count')) {
            $adds[] = fn (Blueprint $table) => $table->integer('usage_count')->default(0);
        }
        if (!Schema::hasColumn('discounts', 'expires_at')) {
            $adds[] = fn (Blueprint $table) => $table->timestamp('expires_at')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'starts_at')) {
            $adds[] = fn (Blueprint $table) => $table->timestamp('starts_at')->nullable();
        }
        if (!Schema::hasColumn('discounts', 'is_active')) {
            $adds[] = fn (Blueprint $table) => $table->boolean('is_active')->default(true);
        }

        if ($adds === []) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) use ($adds) {
            foreach ($adds as $add) {
                $add($table);
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
                // On SQL Server this is nvarchar(max); store JSON text (app encodes arrays).
                $table->json('last_response')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->unique(['discount_id', 'type'], 'discount_sms_deliveries_discount_type_unique');
                $table->index(['status', 'scheduled_for', 'type'], 'discount_sms_deliveries_status_sched_type_idx');
                $table->index(['discount_id'], 'discount_sms_deliveries_discount_id_idx');
            });

            // No cascade FK: deleting a discount must not silently wipe audit/SMS history in production.
            return;
        }

        $adds = [];
        $columns = [
            'discount_id' => fn (Blueprint $t) => $t->unsignedBigInteger('discount_id')->nullable(),
            'type' => fn (Blueprint $t) => $t->string('type', 32)->nullable(),
            'body_id' => fn (Blueprint $t) => $t->unsignedInteger('body_id')->nullable(),
            'recipient' => fn (Blueprint $t) => $t->string('recipient', 20)->nullable(),
            'recipient_name' => fn (Blueprint $t) => $t->string('recipient_name')->nullable(),
            'scheduled_for' => fn (Blueprint $t) => $t->date('scheduled_for')->nullable(),
            'status' => fn (Blueprint $t) => $t->string('status', 20)->nullable(),
            'attempts' => fn (Blueprint $t) => $t->unsignedTinyInteger('attempts')->default(0),
            'provider_reference' => fn (Blueprint $t) => $t->string('provider_reference')->nullable(),
            'last_response' => fn (Blueprint $t) => $t->json('last_response')->nullable(),
            'sent_at' => fn (Blueprint $t) => $t->timestamp('sent_at')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $name => $adder) {
            if (!Schema::hasColumn('discount_sms_deliveries', $name)) {
                $adds[] = $adder;
            }
        }

        if ($adds === []) {
            return;
        }

        Schema::table('discount_sms_deliveries', function (Blueprint $table) use ($adds) {
            foreach ($adds as $add) {
                $add($table);
            }
        });
    }

    /**
     * Only fills NULL sms_enabled. Does not overwrite false/true business choice.
     */
    protected function fillNullSmsEnabledOnly(): void
    {
        if (!Schema::hasColumn('next_purchase_discounts', 'sms_enabled')) {
            return;
        }

        DB::table('next_purchase_discounts')
            ->whereNull('sms_enabled')
            ->update(['sms_enabled' => true]);
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
                'Schema still incomplete after safe ensure migration: ' . implode(', ', $missing)
            );
        }
    }
};
