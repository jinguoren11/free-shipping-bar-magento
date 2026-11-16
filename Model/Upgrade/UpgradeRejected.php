<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Why an upgrade was refused. The code is shown to the person (never raw error text).
 */
class UpgradeRejected extends \RuntimeException
{
    /**
     * @var string
     */
    public $reason;

    /**
     * @param string $reason short code, e.g. BAD_SIGNATURE
     * @param string $message plain explanation
     */
    public function __construct(string $reason, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reason);
        $this->reason = $reason;
    }
}
