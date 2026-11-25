<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Access;
use GetDropFlow\FreeShippingBar\Model\AmountFormatter;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Config;
use GetDropFlow\FreeShippingBar\Model\MessageBuilder;
use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
use GetDropFlow\FreeShippingBar\Model\SectionDataBuilder;
use GetDropFlow\FreeShippingBar\Model\SectionSettings;
use GetDropFlow\FreeShippingBarHyva\ViewModel\InitialState;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support.php';
require_once __DIR__ . '/MagentoStubs.php';
require_once dirname(__DIR__, 4) . '/Compat/Hyva/ViewModel/InitialState.php';

/**
 * F1 (ASUS 355): the Hyva page renders the empty-cart state itself, so it has to ask the same access gate as the
 * customer-data section. A locked store must not show the bar to a first-time visitor.
 */
class HyvaInitialStateTest extends TestCase
{
    private const NOW = 1800000000;

    private function state(Access $access, bool $enabled = true): array
    {
        $store = $this->createStub(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCode')->willReturn('default');
        $stores = $this->createStub(StoreManagerInterface::class);
        $stores->method('getStore')->willReturn($store);
        $locale = $this->createStub(ResolverInterface::class);
        $locale->method('getLocale')->willReturn('en_US');
        $config = $this->createStub(Config::class);
        $config->method('flag')->willReturn($enabled);
        $config->method('getThreshold')->willReturn(50.0);
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
        $viewModel = new InitialState(
            $config,
            new SectionDataBuilder(new ProgressCalculator(), new MessageBuilder()),
            $settings,
            $formatter,
            $stores,
            $locale,
            new Json(),
            $access
        );
        return json_decode($viewModel->getJson(), true);
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

    public function testLockedStoreRendersNoBar(): void
    {
        $data = $this->state($this->gate(false));
        $this->assertFalse($data['visible']);
        $this->assertArrayNotHasKey('state', $data);
    }

    public function testAuthorizedStoreRendersTheEmptyCartBar(): void
    {
        $data = $this->state($this->gate(true));
        $this->assertTrue($data['visible']);
        $this->assertSame('empty', $data['state']);
    }

    public function testTheMarketplaceEditionWithoutTheGateStillShowsTheBar(): void
    {
        $this->assertTrue($this->state(new Access())['visible']);
    }

    /**
     * An older Hyva compat module (1.0.0, left behind by the one-click upgrade) builds the state straight from the
     * builder without asking the gate: the builder bound to the gate in etc/di.xml must still hide the bar.
     */
    public function testTheBuilderItselfHidesTheBarForALockedStoreEvenWhenTheCallerDoesNotAsk(): void
    {
        $call = function (?Access $gate): array {
            $builder = new SectionDataBuilder(new ProgressCalculator(), new MessageBuilder(), $gate);
            $settings = ['messages' => ['progress' => '', 'success' => '', 'empty' => ''], 'bar_color' => '#3b6ea5', 'show_bar' => true,
                'show_percent' => false, 'positions' => ['minicart' => true, 'cart' => true, 'product' => true],
                'selectors' => ['minicart' => '', 'cart' => '', 'product' => ''], 'placement' => 'after'];
            return $builder->build('default', 'en', true, 50.0, true, false, 0.0, $settings, static function (float $a): string {
                return '$' . $a;
            });
        };
        $this->assertFalse($call($this->gate(false))['visible']);
        $this->assertTrue($call($this->gate(true))['visible']);
        $this->assertTrue($call(null)['visible'], 'Marketplace edition: no gate bound');
        $this->assertTrue($call(new Access())['visible']);
    }

    public function testDiBindsTheGateToTheBuilderInTheDownloadEditionOnly(): void
    {
        $root = dirname(__DIR__, 4);
        $this->assertStringContainsString('Model\\SectionDataBuilder', (string) file_get_contents($root . '/etc/di.xml'));
        $this->assertStringContainsString('"access"', (string) file_get_contents($root . '/etc/di.xml'));
    }

    public function testAGateThatBreaksHidesTheBar(): void
    {
        $check = $this->createStub(\GetDropFlow\FreeShippingBar\Model\AccessCheck::class);
        $check->method('isAllowed')->willThrowException(new \RuntimeException('broken'));
        $this->assertFalse($this->state(new Access($check))['visible']);
    }

    public function testSwitchedOffStaysHiddenEvenWhenAuthorized(): void
    {
        $this->assertFalse($this->state($this->gate(true), false)['visible']);
    }
}
