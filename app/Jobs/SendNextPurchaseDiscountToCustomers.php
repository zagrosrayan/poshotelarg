<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use App\Service\SmsService;
use Morilog\Jalali\Jalalian;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNextPurchaseDiscountToCustomers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    protected $mobile;
    protected $discount;
    protected $template;
    protected $data;

    public function __construct($mobile, $discount, $template = null, $data = [])
    {
        $this->mobile = $mobile;
        $this->discount = $discount;
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::error('start send next purchase');
        $smsService = new SmsService();

        $type = ' ریال';
        $discountValue = number_format($this->discount->discount_value);
        $minimumPrice = $this->discount->minimum_price ? number_format($this->discount->minimum_price) : null;
        $startsAt = $this->discount->starts_at ? Jalalian::fromCarbon($this->discount->starts_at)->format('Y/m/d') : null;
        $expiresAt = $this->discount->expires_at ? Jalalian::fromCarbon($this->discount->expires_at)->format('Y/m/d') : null;

        if ($this->template) {
            $text = $this->template;
            $replacements = [
                '{name}' => $this->data['name'] ?? '',
                '{order_number}' => $this->data['order_number'] ?? '',
                '{mobile}' => $this->mobile,
                '{discount_code}' => $this->discount->code,
                '{discount_value}' => $discountValue,
                '{minimum_purchase}' => $minimumPrice,
                '{expiration_date}' => $expiresAt,
            ];

            foreach ($replacements as $key => $value) {
                $text = str_replace($key, $value ?? '', $text);
            }

        } else {
            $text = "🎉 سلام! یه هدیه ویژه برات داریم 🎁\n\n"
                . "تخفیف " . $discountValue . $type . " برای خرید بعدی در هتل ارگ جدید یزد!\n"
                . "کد تخفیف: " . $this->discount->code . "\n"
                . ($minimumPrice ? "حداقل خرید: " . $minimumPrice . " ریال\n" : '')
                . ($startsAt ? "📅 شروع اعتبار: " . $startsAt . "\n" : '')
                . ($expiresAt ? "⏰ اعتبار تا: " . $expiresAt : '');
        }
        Log::error('end send next purchase',[$text]);

        $smsService->send([$this->mobile], $text);
    }

}