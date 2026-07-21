<?php

namespace Tests\Unit;

use App\Service\SmsService;
use PHPUnit\Framework\TestCase;

class SmsServiceParseTest extends TestCase
{
    public function test_parses_xml_recid_as_success(): void
    {
        $service = new SmsService();
        $xml = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://tempuri.org/">5523902944594733956</string>';

        $parsed = $service->parseProviderResult($xml, ['http_code' => 200]);

        $this->assertTrue($parsed['success']);
        $this->assertSame('5523902944594733956', $parsed['value']);
    }

    public function test_parses_negative_error_code(): void
    {
        $service = new SmsService();
        $xml = '<?xml version="1.0" encoding="utf-8"?><string xmlns="http://tempuri.org/">-5</string>';

        $parsed = $service->parseProviderResult($xml, ['http_code' => 200]);

        $this->assertFalse($parsed['success']);
        $this->assertSame('-5', $parsed['value']);
        $this->assertStringContainsString('متغیر', $parsed['message']);
    }

    public function test_normalize_mobile(): void
    {
        $service = new SmsService();

        $this->assertSame('09107860475', $service->normalizeMobile('9107860475'));
        $this->assertSame('09107860475', $service->normalizeMobile('09107860475'));
        $this->assertNull($service->normalizeMobile('123'));
    }
}
