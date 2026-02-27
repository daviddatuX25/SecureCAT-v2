<?php

namespace Tests\Unit\Services;

use App\Services\QrCodeService;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    public function test_admission_slip_data_uri_returns_valid_base64_png(): void
    {
        Config::set('app.url', 'https://app.example.com');

        $service = app(QrCodeService::class);
        $result = $service->admissionSlipDataUri('APP-2026-00123');

        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $this->assertMatchesRegularExpression('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $result);
    }

    public function test_admission_slip_data_uri_decodes_to_valid_png(): void
    {
        Config::set('app.url', 'https://app.example.com');

        $service = new QrCodeService('https://app.example.com');
        $result = $service->admissionSlipDataUri('APP-2026-00123');

        $decoded = base64_decode(substr($result, strpos($result, ',') + 1), true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    public function test_admission_slip_data_uri_differs_by_reference(): void
    {
        Config::set('app.url', 'https://app.example.com');
        $service = new QrCodeService('https://app.example.com');

        $result1 = $service->admissionSlipDataUri('APP-2026-00001');
        $result2 = $service->admissionSlipDataUri('APP-2026-00002');

        $this->assertNotSame($result1, $result2);
    }

    public function test_consultation_data_uri_returns_valid_base64_png(): void
    {
        Config::set('app.url', 'https://app.example.com');

        $service = app(QrCodeService::class);
        $result = $service->consultationDataUri('APP-2026-00123');

        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $this->assertMatchesRegularExpression('/^data:image\/png;base64,[A-Za-z0-9+\/=]+$/', $result);
    }

    public function test_consultation_data_uri_decodes_to_valid_png(): void
    {
        Config::set('app.url', 'https://app.example.com');

        $service = new QrCodeService('https://app.example.com');
        $result = $service->consultationDataUri('APP-2026-00123');

        $decoded = base64_decode(substr($result, strpos($result, ',') + 1), true);
        $this->assertNotFalse($decoded);
        $this->assertStringStartsWith("\x89PNG", $decoded);
    }

    public function test_data_uri_uses_app_url_from_config(): void
    {
        Config::set('app.url', 'https://securecat.test');

        $service = app(QrCodeService::class);
        $result = $service->admissionSlipDataUri('APP-2026-00001');

        $this->assertStringStartsWith('data:image/png;base64,', $result);
        $decoded = base64_decode(substr($result, strpos($result, ',') + 1), true);
        $this->assertNotFalse($decoded);
    }

    public function test_data_uri_produces_different_output_for_different_references(): void
    {
        Config::set('app.url', 'https://app.example.com');
        $service = new QrCodeService('https://app.example.com');

        $result1 = $service->consultationDataUri('APP-2026-00001');
        $result2 = $service->consultationDataUri('APP-2026-00002');

        $this->assertNotSame($result1, $result2);
    }

    public function test_data_uri_custom_size(): void
    {
        Config::set('app.url', 'https://app.example.com');

        $service = new QrCodeService('https://app.example.com');
        $result = $service->dataUri('https://app.example.com/test', 120);

        $this->assertStringStartsWith('data:image/png;base64,', $result);
    }
}
