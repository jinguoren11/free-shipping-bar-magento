<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

/**
 * Static consistency checks for the Breeze layout. Whether it really works is decided by the Breeze test (TR-009).
 */
class BreezeCompatTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';
    private const COMPONENT = 'GetDropFlow_FreeShippingBar/js/view/bar';

    private function xml(): string
    {
        return (string) file_get_contents(self::ROOT . '/view/frontend/layout/breeze_default.xml');
    }

    public function testRegistersTheComponentUnderTheNameTheJsFileDeclares(): void
    {
        $this->assertStringContainsString('<item name="' . self::COMPONENT . '" xsi:type="array">', $this->xml());
        $js = (string) file_get_contents(self::ROOT . '/view/frontend/web/js/view/bar.js');
        $this->assertStringContainsString("component: '" . self::COMPONENT . "'", $js);
        $this->assertFileExists(self::ROOT . '/view/frontend/web/js/view/bar.js');
    }

    public function testMiniCartUsesBreezeRegionAndDisablesTheLumaOne(): void
    {
        $sx = simplexml_load_string($this->xml());
        $this->assertNotFalse($sx);
        $this->assertStringContainsString('name="extraInfo"', $this->xml());
        $this->assertStringContainsString('name="componentDisabled"', $this->xml());
        // the Luma layout puts the same component in "extra_info"; the Breeze file must disable exactly that one
        $luma = (string) file_get_contents(self::ROOT . '/view/frontend/layout/default.xml');
        $this->assertStringContainsString('name="extra_info"', $luma);
        $this->assertStringContainsString('name="free_shipping_bar"', $luma);
    }

    public function testHandleIsBreezeOnlyAndNeverTouchesOtherThemes(): void
    {
        $this->assertFileExists(self::ROOT . '/view/frontend/layout/breeze_default.xml');
        foreach (glob(self::ROOT . '/view/frontend/layout/*.xml') ?: [] as $file) {
            if (basename($file) !== 'breeze_default.xml') {
                $this->assertStringNotContainsString('breeze.js', (string) file_get_contents($file));
            }
        }
    }
}
