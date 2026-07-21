<?php

namespace App\Jobs;

use App\Service\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderCompleteSms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mobile;
    protected $template;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @param string|array $mobile
     * @param string $template
     * @param array $data
     */
    public function __construct($mobile, $template, $data = [])
    {
        $this->mobile = is_array($mobile) ? $mobile : [$mobile];
        $this->template = $template;
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->template)) {
            return;
        }

        $smsService = new SmsService();

        $text = $this->template;
        $replacements = [
            '{name}' => $this->data['name'] ?? '',
            '{order_number}' => $this->data['order_number'] ?? '',
            '{price}' => isset($this->data['price']) ? number_format($this->data['price']) : '',
            '{date}' => $this->data['date'] ?? '',
        ];

        foreach ($replacements as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        $smsService->send($this->mobile, $text);
    }
}