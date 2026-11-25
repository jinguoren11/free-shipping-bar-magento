<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Address and public key of the GetDropFlow license server. Public keys are safe to ship (same key as the other GetDropFlow plugins).
 */
class ServerKey
{
    public const BASE_URL = 'https://license.getdropflow.com';
    public const PUBLIC_KEY_BASE64 = 'hEa289v4xzpVxiFRoGNIwyTix7GkyUuUmBqWtjblYGM=';
    /** Short product name sent to /v1/link/start (the account's free membership is used). */
    public const PRODUCT = 'shipping-bar';
    /** An answer older than this is no longer "valid now" (offline rules decide). */
    public const MAX_AGE_SECONDS = 172800;
}
