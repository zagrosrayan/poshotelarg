<?php

namespace App\Service;

use App\Models\Sms;

class SmsService
{
    public function send(array $to, string $text): array
    {
        $username = env('API_USERNAME_MELI_PAYAMAK');
        $password = env('API_KEY_MELI_PAYAMAK');
        $from     = env('API_FROM_MELI_PAYAMAK');

        $url = 'https://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS2';

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

            $ch = curl_init();

            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query($postData),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/x-www-form-urlencoded'
                ],

                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $response = curl_exec($ch);

            $curlError = curl_error($ch);
            $curlErrNo = curl_errno($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($curlErrNo) {

                Sms::create([
                    'text'     => $text,
                    'to'       => [$number],
                    'from'     => $from,
                    'response' => [
                        'curl_error' => $curlError,
                        'curl_errno' => $curlErrNo
                    ],
                    'status'   => 'failed',
                ]);

                $results[] = [
                    'number' => $number,
                    'status' => 'failed',
                    'error'  => $curlError,
                ];

                continue;
            }

            $response = trim($response);

            $success = ctype_digit($response) && (int)$response > 100;

            Sms::create([
                'text'     => $text,
                'to'       => [$number],
                'from'     => $from,
                'response' => [
                    'raw'       => $response,
                    'http_code' => $httpCode,
                ],
                'status'   => $success ? 'success' : 'failed',
            ]);

            $results[] = [
                'number'    => $number,
                'status'    => $success ? 'success' : 'failed',
                'response'  => $response,
                'http_code' => $httpCode,
            ];
        }

        return $results;
    }
}