<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Cron;

use GetDropFlow\FreeShippingBar\Model\Authorization\StatusService;

/**
 * Download edition only. Every six hours: asks the license server whether this store is still authorized and keeps the
 * signed answer. Does nothing when the store has no credential. A website that cannot be reached changes nothing.
 */
class RefreshAuthorization
{
    /**
     * @var StatusService
     */
    private $status;

    /**
     * @param StatusService $status
     */
    public function __construct(StatusService $status)
    {
        $this->status = $status;
    }

    /**
     * Runs the check.
     *
     * @return void
     */
    public function execute(): void
    {
        $this->status->refresh();
    }
}
