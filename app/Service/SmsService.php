<?php

namespace App\Service;

use App\Models\Sms;

class SmsService
{
    public function send(array $to, $text)
    {
        $username = env('API_USERNAME_MELI_PAYAMAK');
        $password = env('API_KEY_MELI_PAYAMAK');
        $from     = env('API_FROM_MELI_PAYAMAK');

        $results = [];

        foreach ($to as $number) {
            try {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "http://api.payamak-panel.com/post/Send.asmx/SendSimpleSMS");
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                    'username' => $username,
                    'password' => $password,
                    'from'     => $from,
                    'to'       => $number,
                    'text'     => $text,
                    'isflash'  => false,
                ]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $resultArray = simplexml_load_string($response);
                $statusCode = (string) $resultArray->SendSimpleSMSResult ?? '0';

                $success = ($statusCode === '1');

                Sms::create([
                    'text'     => $text,
                    'to'       => [$number],
                    'from'     => $from,
                    'response' => ['raw' => $response, 'statusCode' => $statusCode],
                    'status'   => $success ? 'success' : 'failed',
                ]);

                $results[] = [
                    'number' => $number,
                    'status' => $success ? 'success' : 'failed',
                    'code'   => $statusCode,
                ];

            } catch (\Exception $e) {
                Sms::create([
                    'text'     => $text,
                    'to'       => [$number],
                    'from'     => $from,
                    'response' => ['error' => $e->getMessage()],
                    'status'   => 'failed',
                ]);

                $results[] = [
                    'number' => $number,
                    'status' => 'failed',
                    'error'  => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}