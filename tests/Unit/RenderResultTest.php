<?php

namespace Tests\Unit;

use App\ValueObjects\RenderResult;
use PHPUnit\Framework\TestCase;

class RenderResultTest extends TestCase
{
    public function test_constructs_with_all_properties(): void
    {
        $result = new RenderResult(
            html: '<div>test</div>',
            mode: 'html',
            paperSize: 'a4',
            orientation: 'portrait',
            logicalUnit: 'full',
        );

        $this->assertSame('<div>test</div>', $result->html);
        $this->assertSame('html', $result->mode);
        $this->assertSame('a4', $result->paperSize);
        $this->assertSame('portrait', $result->orientation);
        $this->assertSame('full', $result->logicalUnit);
    }

    public function test_is_half_returns_true_for_half_units(): void
    {
        $halfA4 = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'half_a4');
        $halfLetter = new RenderResult(html: '', mode: 'html', paperSize: 'letter', orientation: 'portrait', logicalUnit: 'half_letter');
        $full = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');

        $this->assertTrue($halfA4->isHalf());
        $this->assertTrue($halfLetter->isHalf());
        $this->assertFalse($full->isHalf());
    }

    public function test_page_dimensions_returns_correct_mm(): void
    {
        $a4Portrait = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $legalLandscape = new RenderResult(html: '', mode: 'html', paperSize: 'legal', orientation: 'landscape', logicalUnit: 'full');
        $halfA4 = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'half_a4');

        $this->assertEquals(['width' => 210, 'height' => 297], $a4Portrait->pageDimensions());
        $this->assertEquals(['width' => 356, 'height' => 216], $legalLandscape->pageDimensions());
        $this->assertEquals(['width' => 210, 'height' => 148], $halfA4->pageDimensions());
    }

    public function test_css_page_size_returns_css_string(): void
    {
        $a4Portrait = new RenderResult(html: '', mode: 'html', paperSize: 'a4', orientation: 'portrait', logicalUnit: 'full');
        $letterLandscape = new RenderResult(html: '', mode: 'html', paperSize: 'letter', orientation: 'landscape', logicalUnit: 'full');

        $this->assertSame('a4 portrait', $a4Portrait->cssPageSize());
        $this->assertSame('letter landscape', $letterLandscape->cssPageSize());
    }
}
