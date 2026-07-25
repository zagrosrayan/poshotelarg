<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * OPTIONAL data heal for next-purchase centers.
 * Default is dry-run. Requires --force to write.
 */
class HealNextPurchaseData extends Command
{
    protected $signature = 'sms:heal-np-data
        {--force : Actually write changes (without this flag only dry-run)}
        {--sync-codes : Also sync active unused NP codes to current settings profit_manager_ids}';

    protected $description = 'Dry-run/safe optional heal for next-purchase settings/codes (DB data, not schema)';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $syncCodes = (bool) $this->option('sync-codes');

        $this->warn($force
            ? 'WRITE MODE enabled (--force)'
            : 'DRY-RUN only (pass --force to apply)');

        if (!Schema::hasTable('next_purchase_discounts')) {
            $this->error('next_purchase_discounts missing');
            return self::FAILURE;
        }

        $allPmIds = Schema::hasTable('profit_managers')
            ? DB::table('profit_managers')->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        $settings = DB::table('next_purchase_discounts')->where('is_active', 1)->orderByDesc('id')->get();
        $settingsChanges = 0;

        foreach ($settings as $setting) {
            $pmIds = $this->decode($setting->profit_manager_ids ?? null);
            $targets = $this->decode($setting->target_customer_types ?? null);
            $payload = [];

            if ($pmIds === [] && $allPmIds !== []) {
                $payload['profit_manager_ids'] = json_encode(array_values($allPmIds));
                $this->line("settings#{$setting->id}: empty profit_manager_ids -> all centers " . json_encode($allPmIds));
            }

            if ($targets === []) {
                $payload['target_customer_types'] = json_encode(['resident', 'Non_resident']);
                $this->line("settings#{$setting->id}: empty target_customer_types -> resident+Non_resident");
            }

            if ($payload === []) {
                continue;
            }

            $settingsChanges++;
            if ($force) {
                $payload['updated_at'] = now();
                DB::table('next_purchase_discounts')->where('id', $setting->id)->update($payload);
            }
        }

        $codeChanges = 0;
        if ($syncCodes && Schema::hasTable('discounts') && Schema::hasColumn('discounts', 'profit_manager_ids')) {
            $latest = DB::table('next_purchase_discounts')->where('is_active', 1)->orderByDesc('id')->first();
            $pm = $this->decode($latest->profit_manager_ids ?? null);
            if ($latest && $pm !== []) {
                $q = DB::table('discounts')
                    ->where('scope', 'next_purchase')
                    ->where('is_active', 1)
                    ->where(function ($w) {
                        $w->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
                if (Schema::hasColumn('discounts', 'usage_count') && Schema::hasColumn('discounts', 'usage_limit')) {
                    $q->whereColumn('usage_count', '<', 'usage_limit');
                }
                $codeChanges = (clone $q)->count();
                $this->line("NP codes that would sync profit_manager_ids: {$codeChanges} -> " . json_encode($pm));
                if ($force && $codeChanges > 0) {
                    $q->update([
                        'profit_manager_ids' => json_encode(array_values(array_map('intval', $pm))),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        $this->info("settings_rows_touched={$settingsChanges} codes_touched={$codeChanges}");
        if (!$force) {
            $this->comment('No writes performed. Re-run with --force after review.');
        }

        return self::SUCCESS;
    }

    protected function decode($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return array_values($value);
        }
        if (!is_string($value)) {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }
}
