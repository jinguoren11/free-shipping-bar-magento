<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Current time as a Unix timestamp (a class of its own so tests can set the time).
 */
class Clock
{
    /**
     * Now.
     *
     * @return int
     */
    public function now(): int
    {
        return time();
    }
}
