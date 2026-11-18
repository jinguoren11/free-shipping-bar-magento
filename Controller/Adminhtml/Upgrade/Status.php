<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Controller\Adminhtml\Upgrade;

use Magento\Framework\Controller\ResultInterface;

/**
 * Progress of a running upgrade, read from the status file (no network). Also restores an upgrade that died.
 */
class Status extends AbstractUpgrade
{
    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $service = $this->factory->create();
        $service->recoverIfDead();

        return $this->json->create()->setData($this->describe($service, $service->peek()));
    }
}
