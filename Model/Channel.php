<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Which edition this package is. The build script (build/package.sh --channel=...) rewrites the value.
 * "direct" = downloaded from getdropflow.com / GitHub (has the one-click upgrade).
 * "marketplace" = Adobe Commerce Marketplace edition (no code that downloads code).
 */
class Channel
{
    public const DIRECT = 'direct';
    public const MARKETPLACE = 'marketplace';
    public const CURRENT = 'direct'; // CHANNEL-VALUE

    /**
     * Tells whether this is the download edition (with one-click upgrade).
     *
     * @return bool
     */
    public function isDirect(): bool
    {
        return self::CURRENT === self::DIRECT;
    }
}
