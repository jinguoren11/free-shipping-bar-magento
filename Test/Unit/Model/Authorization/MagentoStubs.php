<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

// Minimal stand-ins for the few Magento types the storefront section touches, declared only when Magento itself is not loaded.

namespace Magento\Customer\CustomerData {
    if (!interface_exists(SectionSourceInterface::class)) {
        interface SectionSourceInterface
        {
            public function getSectionData();
        }
    }
}

namespace Magento\Framework\Locale {
    if (!interface_exists(ResolverInterface::class)) {
        interface ResolverInterface
        {
            public function getLocale();
        }
    }
}

namespace Magento\Store\Api\Data {
    if (!interface_exists(StoreInterface::class)) {
        interface StoreInterface
        {
            public function getId();
            public function getCode();
        }
    }
}

namespace Magento\Store\Model {
    if (!interface_exists(StoreManagerInterface::class)) {
        interface StoreManagerInterface
        {
            public function getStore();
        }
    }
}

namespace Magento\Framework\Pricing {
    if (!interface_exists(PriceCurrencyInterface::class)) {
        interface PriceCurrencyInterface
        {
        }
    }
}

namespace Magento\Framework\App\Config {
    if (!interface_exists(ScopeConfigInterface::class)) {
        interface ScopeConfigInterface
        {
        }
    }
}

namespace Magento\Quote\Model {
    if (!class_exists(Quote::class)) {
        /** A cart that can only be read: any other call fails the test. */
        class Quote
        {
            public $subtotal = 40.0;

            public function getId()
            {
                return 7;
            }

            public function getItemsQty()
            {
                return 2;
            }

            public function isVirtual()
            {
                return false;
            }

            public function getBaseSubtotalWithDiscount()
            {
                return $this->subtotal;
            }

            public function __call($name, $args)
            {
                throw new \LogicException('the bar must only read the cart, not call ' . $name);
            }
        }
    }
}

namespace Magento\Checkout\Model {
    if (!class_exists(Session::class)) {
        class Session
        {
            public $quote;

            public function getQuote()
            {
                return $this->quote;
            }
        }
    }
}
