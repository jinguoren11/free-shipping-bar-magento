<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\CustomerData\FreeShippingBar;
use GetDropFlow\FreeShippingBar\Model\Access;
use GetDropFlow\FreeShippingBar\Model\AmountFormatter;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\MessageBuilder;
use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use GetDropFlow\FreeShippingBar\Model\SectionSettings;
use Magento\Checkout\Model\Session;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/MagentoStubs.php';

/**
 * The real storefront section source with the real builder: locked hides the bar, authorized shows it exactly as before,
 * and in both cases the cart is only read (the quote stub fails on any other call).
 */
class SectionGateTest extends TestCase
{
    private const NOW = 1800000000;

    private function section(Access $access, Quote $quote): array
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCode')->willReturn('default');
        $stores = $this->createStub(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);
        $locale = $this->createStub(ResolverInterface::class);
        $locale->method('getLocale')->willReturn('es_ES');
        $session = new Session();
        $session->quote = $quote;
        $config = $this->createStub(Config::class);
        $config->method('flag')->willReturn(true);
        $config->method('getThreshold')->willReturn(100.0);
        $settings = $this->createStub(SectionSettings::class);
        $settings->method('get')->willReturn([
            'messages' => ['progress' => '', 'success' => '', 'empty' => ''], 'bar_color' => '#3b6ea5', 'show_bar' => true,
            'show_percent' => false, 'positions' => ['minicart' => true, 'cart' => true, 'product' => true],
            'selectors' => ['minicart' => '', 'cart' => '', 'product' => ''], 'placement' => 'after',
        ]);
        $formatter = $this->createStub(AmountFormatter::class);
        $formatter->method('format')->willReturnCallback(static function (float $a): string {
            return '$' . number_format($a, 2);
        });
        $source = new FreeShippingBar(
            $config,
            new SectionDataBuilder(new ProgressCalculator(), new MessageBuilder()),
            $session,
            $formatter,
            $settings,
            $stores,
            $locale,
            $access
        );
        return $source->getSectionData();
    }

    private function gate(bool $authorized): Access
    {
        $store = new MemoryStore();
        if ($authorized) {
            $store->credential = 'gdt_' . str_repeat('a', 43);
            $store->record = ['state' => 'valid', 'answer_at' => self::NOW, 'last_valid_at' => self::NOW];
        }
        return new Access(new AuthorizationGate($store, new FixedClock(self::NOW)));
    }

    public function testAuthorizedShowsTheBarWithTheUsualContent(): void
    {
        $data = $this->section($this->gate(true), new Quote());
        $this->assertTrue($data['visible']);
        $this->assertSame('progress', $data['state']);
    }

    public function testLockedHidesTheBarAndOnlyReadsTheCart(): void
    {
        $data = $this->section($this->gate(false), new Quote());
        $this->assertFalse($data['visible']);
        $this->assertSame('default', $data['store_code']);
        $this->assertArrayNotHasKey('state', $data);
    }

    public function testTheMarketplaceEditionIsNeverLocked(): void
    {
        $this->assertTrue($this->section(new Access(), new Quote())['visible']);
    }

    public function testAnEmptyOrFullCartBehavesTheSameWhenAuthorized(): void
    {
        $full = new Quote();
        $full->subtotal = 150.0;
        $this->assertSame('done', $this->section($this->gate(true), $full)['state'] ?? null);
    }
}
