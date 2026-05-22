<?php

namespace Tests\Unit;

use App\Services\ResultSheetTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultSheetTemplateServiceHelperTest extends TestCase
{
    use RefreshDatabase;

    private ResultSheetTemplateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ResultSheetTemplateService::class);
    }

    public function test_extract_numeric_from_ordinal(): void
    {
        $method = new \ReflectionMethod($this->service, 'extractNumeric');
        $method->setAccessible(true);

        $this->assertSame(85, $method->invoke($this->service, '85th'));
        $this->assertSame(99, $method->invoke($this->service, '99+'));
        $this->assertSame(1, $method->invoke($this->service, '1st'));
        $this->assertSame(0, $method->invoke($this->service, 'N/A'));
        $this->assertSame(42, $method->invoke($this->service, '42'));
    }

    public function test_format_ordinal(): void
    {
        $method = new \ReflectionMethod($this->service, 'formatOrdinal');
        $method->setAccessible(true);

        $this->assertSame('1st', $method->invoke($this->service, 1));
        $this->assertSame('2nd', $method->invoke($this->service, 2));
        $this->assertSame('3rd', $method->invoke($this->service, 3));
        $this->assertSame('4th', $method->invoke($this->service, 4));
        $this->assertSame('11th', $method->invoke($this->service, 11));
        $this->assertSame('12th', $method->invoke($this->service, 12));
        $this->assertSame('13th', $method->invoke($this->service, 13));
        $this->assertSame('21st', $method->invoke($this->service, 21));
        $this->assertSame('22nd', $method->invoke($this->service, 22));
        $this->assertSame('23rd', $method->invoke($this->service, 23));
        $this->assertSame('100th', $method->invoke($this->service, 100));
    }

    public function test_extract_numeric_from_special_strings(): void
    {
        $method = new \ReflectionMethod($this->service, 'extractNumeric');
        $method->setAccessible(true);

        $this->assertSame(0, $method->invoke($this->service, '—'));
        $this->assertSame(0, $method->invoke($this->service, ''));
        $this->assertSame(0, $method->invoke($this->service, 'abc'));
    }
}
