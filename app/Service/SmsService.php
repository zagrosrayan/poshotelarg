<?php

namespace App\Service;

use App\Models\Sms;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(array $to, string $text): array
    {
        $username = env('API_USERNAME_MELI_PAYAMAK');
        $password = env('API_KEY_MELI_PAYAMAK');
        $from     = env('API_FROM_MELI_PAYAMAK');

        $url = 'http://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2';

        $results = [];

        foreach ($to as $number) {
            $postData = [
                'username' => $username,
                'password' => $password,
                'from'     => $from,
                'to'       => $number,
                'text'     => $text,
                'isflash'  => 'false',
            ];

            $rawResult = $this->postForm($url, $postData);
            $parsed = $this->parseProviderResult($rawResult['body'] ?? '', $rawResult);

            if (!empty($rawResult['curl_errno'])) {
                Sms::create([
                    'text'     => $text,
                    'to'       => [$number],
                    'from'     => $from,
                    'response' => [
                        'curl_error' => $rawResult['curl_error'] ?? null,
                        'curl_errno' => $rawResult['curl_errno'],
                    ],
                    'status'   => 'failed',
                ]);

                $results[] = [
                    'number' => $number,
                    'status' => 'failed',
                    'error'  => $rawResult['curl_error'] ?? 'curl error',
                ];

                continue;
            }

            Sms::create([
                'text'     => $text,
                'to'       => [$number],
                'from'     => $from,
                'response' => [
                    'raw'       => $rawResult['body'] ?? null,
                    'value'     => $parsed['value'],
                    'http_code' => $rawResult['http_code'] ?? null,
                ],
                'status'   => $parsed['success'] ? 'success' : 'failed',
            ]);

            $results[] = [
                'number'    => $number,
                'status'    => $parsed['success'] ? 'success' : 'failed',
                'response'  => $parsed['value'],
                'http_code' => $rawResult['http_code'] ?? null,
                'message'   => $parsed['message'],
            ];
        }

        return $results;
    }

    /**
     * Send patterned SMS via Melipayamak SendByBaseNumber2.
     *
     * @param  array<int, string>  $variables
     * @return array{success: bool, message: string, rec_id: ?string, value: ?string, raw: ?string, http_code: ?int}
     */
    public function sendByBaseNumber2(string $to, int $bodyId, array $variables): array
    {
        $to = $this->normalizeMobile($to);
        if (!$to) {
            return [
                'success'   => false,
                'message'   => 'شماره موبایل نامعتبر است',
                'rec_id'    => null,
                'value'     => null,
                'raw'       => null,
                'http_code' => null,
            ];
        }

        $username = env('API_USERNAME_MELI_PAYAMAK');
        $password = env('API_KEY_MELI_PAYAMAK');
        $from     = env('API_FROM_MELI_PAYAMAK');

        $url = 'http://api.payamak-panel.com/post/Send.asmx/SendByBaseNumber2';
        $text = implode(';', array_map(static fn ($v) => (string) $v, $variables));

        $rawResult = $this->postForm($url, [
            'username' => $username,
            'password' => $password,
            'to'       => $to,
            'bodyId'   => $bodyId,
            'text'     => $text,
        ]);

        $parsed = $this->parseProviderResult($rawResult['body'] ?? '', $rawResult);

        Sms::create([
            'text'     => 'pattern:' . $bodyId . '|' . $text,
            'to'       => [$to],
            'from'     => $from,
            'response' => [
                'body_id'   => $bodyId,
                'raw'       => $rawResult['body'] ?? null,
                'value'     => $parsed['value'],
                'http_code' => $rawResult['http_code'] ?? null,
                'curl_error'=> $rawResult['curl_error'] ?? null,
            ],
            'status'   => $parsed['success'] ? 'success' : 'failed',
        ]);

        if (!$parsed['success']) {
            Log::warning('Payamak SendByBaseNumber2 failed', [
                'to'      => $to,
                'body_id' => $bodyId,
                'value'   => $parsed['value'],
                'message' => $parsed['message'],
            ]);
        }

        return [
            'success'   => $parsed['success'],
            'message'   => $parsed['message'],
            'rec_id'    => $parsed['success'] ? $parsed['value'] : null,
            'value'     => $parsed['value'],
            'raw'       => $rawResult['body'] ?? null,
            'http_code' => $rawResult['http_code'] ?? null,
        ];
    }

    public function normalizeMobile(?string $mobile): ?string
    {
        if ($mobile === null || $mobile === '') {
            return null;
        }

        $mobile = preg_replace('/[^0-9]/', '', $mobile);

        if (strlen($mobile) === 10 && str_starts_with($mobile, '9')) {
            $mobile = '0' . $mobile;
        }

        if (preg_match('/^09[0-9]{9}$/', $mobile)) {
            return $mobile;
        }

        return null;
    }

    /**
     * @param  array{curl_errno?: int, curl_error?: string, http_code?: int}  $meta
     * @return array{success: bool, value: ?string, message: string}
     */
    public function parseProviderResult(?string $body, array $meta = []): array
    {
        if (!empty($meta['curl_errno'])) {
            return [
                'success' => false,
                'value'   => null,
                'message' => $meta['curl_error'] ?? 'خطای ارتباط با سرویس پیامک',
            ];
        }

        $body = trim((string) $body);
        if ($body === '') {
            return [
                'success' => false,
                'value'   => null,
                'message' => 'پاسخ خالی از سرویس پیامک',
            ];
        }

        $value = $this->extractResultValue($body);

        if ($value === null || $value === '') {
            return [
                'success' => false,
                'value'   => null,
                'message' => 'پاسخ نامعتبر از سرویس پیامک',
            ];
        }

        if (ctype_digit($value) && strlen($value) > 15) {
            return [
                'success' => true,
                'value'   => $value,
                'message' => 'پیامک با موفقیت ارسال شد',
            ];
        }

        return [
            'success' => false,
            'value'   => $value,
            'message' => $this->getErrorMessage($value),
        ];
    }

    protected function extractResultValue(string $body): ?string
    {
        $trimmed = trim($body);

        if (ctype_digit($trimmed) || preg_match('/^-?\d+$/', $trimmed)) {
            return $trimmed;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($trimmed, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            if (preg_match('/>(-?\d+)</', $trimmed, $matches)) {
                return $matches[1];
            }

            return null;
        }

        $nodes = $xml->xpath('//*[contains(local-name(), "Result") or local-name()="string"]');
        if (!empty($nodes)) {
            return trim((string) $nodes[0]);
        }

        $asString = trim((string) $xml);
        if ($asString !== '') {
            return $asString;
        }

        return null;
    }

    protected function getErrorMessage(string $code): string
    {
        $errors = [
            '0'   => 'نام کاربری یا رمز عبور صحیح نمی‌باشد',
            '1'   => 'دسترسی برای استفاده از این وبسرویس غیرفعال است',
            '2'   => 'اعتبار کافی نمی‌باشد',
            '3'   => 'خط ارسالی در سیستم تعریف نشده است',
            '4'   => 'کد متن ارسالی صحیح نمی‌باشد و یا توسط مدیر سامانه تأیید نشده است',
            '5'   => 'متن ارسالی با متغیرهای مشخص شده همخوانی ندارد',
            '6'   => 'خطای داخلی رخ داده است',
            '7'   => 'متن حاوی کلمه فیلتر شده می‌باشد',
            '10'  => 'کاربر مورد نظر فعال نمی‌باشد / ممنوعیت ارسال لینک در متغیرها',
            '11'  => 'ارسال نشده',
            '12'  => 'مدارک کاربر کامل نمی‌باشد',
            '18'  => 'شماره موبایل معتبر نمی‌باشد',
            '19'  => 'سقف محدودیت روزانه ارسال از وبسرویس',
            '108' => 'IP مسدود شده است',
            '109' => 'تنظیم IP مجاز الزامی است',
            '110' => 'استفاده از ApiKey الزامی است',
        ];

        $cleanCode = ltrim($code, '-');

        return $errors[$cleanCode] ?? "خطای نامشخص (کد: {$code})";
    }

    /**
     * @return array{body: ?string, http_code: int, curl_errno: int, curl_error: string}
     */
    protected function postForm(string $url, array $postData): array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($postData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return [
            'body'       => is_string($response) ? $response : null,
            'http_code'  => $httpCode,
            'curl_errno' => $curlErrNo,
            'curl_error' => $curlError,
        ];
    }
}
