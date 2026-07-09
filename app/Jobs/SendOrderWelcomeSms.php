<?php

namespace App\Jobs;

use App\Service\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderWelcomeSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mobile;
    protected $customerName;
    protected $orderNumber;

    /**
     * Create a new job instance.
     */
    public function __construct($mobile, $customerName, $orderNumber)
    {
        $this->mobile = $mobile;
        $this->customerName = $customerName;
        $this->orderNumber = $orderNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $smsService = new SmsService();

        $text = "🌸 سلام {$this->customerName} عزیز!\n\n"
            . "سفارش شما با شماره {$this->orderNumber} با موفقیت ثبت شد ✅\n"
            . "خیلی خوشحالیم که هتل ارگ جدید یزد رو انتخاب کردی 😍\n"
            . "منتظرتیم برای تجربه‌ای شیرین و بی‌نظیر! 🎉\n\n"
            . "با سپاس، تیم هتل ارگ.";

        $smsService->send($this->mobile, $text);
    }
}
