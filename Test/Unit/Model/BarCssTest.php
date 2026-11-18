<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

/**
 * Regression test for C1b: Luma sets font-size: 0 on .header.links; a child theme that moves the mini cart there
 * made the bar inherit 0 px and shrink to 2 px. Every box that holds text must set its own non-zero size.
 */
class BarCssTest extends TestCase
{
    private function css(): string
    {
        $css = (string) file_get_contents(__DIR__ . '/../../../view/frontend/web/css/bar.css');
        return (string) preg_replace('#/\*.*?\*/#s', '', $css); // comments explain the problem and mention the bad values
    }

    private function rule(string $selector): string
    {
        $this->assertSame(1, preg_match('/(?:^|\})\s*' . preg_quote($selector, '/') . '\s*\{([^}]*)\}/m', $this->css(), $m), $selector);
        return $m[1];
    }

    /**
     * @dataProvider boxes
     */
    public function testBoxSetsItsOwnFontSize(string $selector): void
    {
        $this->assertMatchesRegularExpression('/font-size:\s*(var\(--gdf-fsb-font-size,\s*\d+px\)|\d+px)/', $this->rule($selector));
    }

    public static function boxes(): array
    {
        return [['.gdf-fsb-mount'], ['.gdf-fsb-bar']];
    }

    public function testNothingInheritsTheSizeOrUsesZero(): void
    {
        $this->assertStringNotContainsString('font: inherit', $this->css());
        $this->assertDoesNotMatchRegularExpression('/font-size:\s*(inherit|0\b)/', $this->css());
    }

    public function testNoNamedColors(): void
    {
        $this->assertDoesNotMatchRegularExpression('/(?<![-\w])(red|blue|green|white|black|transparent)(?![-\w])/i', $this->css());
    }
}
