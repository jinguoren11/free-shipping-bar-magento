<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * The only thing the storefront code asks. Fails safe for the shop: whatever goes wrong inside the check, the answer is
 * "do not show the bar"; nothing here can stop a cart, a checkout or an order.
 */
class Access
{
    /**
     * @var AccessCheck|null
     */
    private $check;

    /**
     * @param AccessCheck|null $check null in the Marketplace edition (not enforced)
     */
    public function __construct(?AccessCheck $check = null)
    {
        $this->check = $check;
    }

    /**
     * May the bar be shown?
     *
     * @return bool
     */
    public function isAllowed(): bool
    {
        if ($this->check === null) {
            return true;
        }
        try {
            return $this->check->isAllowed();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
