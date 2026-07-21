<?php

namespace App\Console\Commands;

use App\Service\NextPurchaseDiscountSmsScheduler;
use Illuminate\Console\Command;

class DispatchDiscountPatternSms extends Command
{
    protected $signature = 'sms:dispatch-discount-patterns';

    protected $description = 'Send due next-purchase discount pattern SMS (issued and reminder)';

    public function handle(NextPurchaseDiscountSmsScheduler $scheduler): int
    {
        $stats = $scheduler->dispatchDue();

        $this->info(sprintf(
            'Discount SMS dispatch finished. sent=%d failed=%d cancelled=%d skipped=%d',
            $stats['sent'],
            $stats['failed'],
            $stats['cancelled'],
            $stats['skipped']
        ));

        return self::SUCCESS;
    }
}
