<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * The public key that checks the signature of latest.json. Public keys are safe to ship.
 * The real release key (generated 2026-11-18 with the owner's approval). The private key is kept outside this repository. If this constant is empty, one-click upgrade stays off.
 */
class ReleaseKey
{
    public const PUBLIC_KEY_BASE64 = 'Y13LfOlijOWiLsELRoUtp3SAQyTTYAYmnnLh0Mdt9Bo=';
}
