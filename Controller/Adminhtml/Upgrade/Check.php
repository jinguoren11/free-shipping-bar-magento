<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Controller\Adminhtml\Upgrade;

use Magento\Framework\Controller\ResultInterface;

/**
 * "Check for updates": the only place that contacts GitHub (and only when the button is pressed).
 */
class Check extends AbstractUpgrade
{
    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $service = $this->factory->create();
        $service->recoverIfDead();

        return $this->json->create()->setData($this->describe($service, $service->check(true)));
    }
}
