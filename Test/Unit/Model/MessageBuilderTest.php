<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use GetDropFlow\FreeShippingBar\Model\MessageBuilder;
use GetDropFlow\FreeShippingBar\Model\ProgressCalculator;
use PHPUnit\Framework\TestCase;

class MessageBuilderTest extends TestCase
{
    public function testBuiltInProgress(): void
    {
        $this->assertSame(
            'Add $10.00 more to get free shipping!',
            (new MessageBuilder())->build(ProgressCalculator::STATE_PROGRESS, '', '$10.00', '$50.00')
        );
    }

    public function testBuiltInEmptyShowsThreshold(): void
    {
        $this->assertSame(
            'Free shipping on orders over $50.00',
            (new MessageBuilder())->build(ProgressCalculator::STATE_EMPTY, '   ', '$50.00', '$50.00')
        );
    }

    public function testBuiltInDone(): void
    {
        $this->assertSame(
            'Congratulations! You have free shipping.',
            (new MessageBuilder())->build(ProgressCalculator::STATE_DONE, '', '$0.00', '$50.00')
        );
    }

    public function testAdminOverrideReplacesPlaceholders(): void
    {
        $this->assertSame(
            'Faltan 10,00 € de 50,00 €',
            (new MessageBuilder())->build(
                ProgressCalculator::STATE_PROGRESS,
                'Faltan {{amount}} de {{threshold}}',
                '10,00 €',
                '50,00 €'
            )
        );
    }
}
