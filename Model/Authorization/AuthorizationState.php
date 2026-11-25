<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * The answer of AuthorizationPolicy: the state and whether it holds only because the website cannot be reached.
 */
class AuthorizationState
{
    /** @var string */
    public $state;
    /** @var bool */
    public $offline;

    /**
     * @param string $state
     * @param bool $offline
     */
    public function __construct(string $state, bool $offline = false)
    {
        $this->state = $state;
        $this->offline = $offline;
    }

    /**
     * Everything but LOCKED may use the plugin.
     *
     * @return bool
     */
    public function allowsUse(): bool
    {
        return $this->state !== AuthorizationPolicy::LOCKED;
    }

    /**
     * Is the plugin locked?
     *
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->state === AuthorizationPolicy::LOCKED;
    }
}
