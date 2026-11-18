<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * The public key that checks the signature of latest.json. Public keys are safe to ship.
 * Empty until the owner has approved and generated the real signing key; while empty, one-click upgrade stays off.
 */
class ReleaseKey
{
    public const PUBLIC_KEY_BASE64 = '';
}
