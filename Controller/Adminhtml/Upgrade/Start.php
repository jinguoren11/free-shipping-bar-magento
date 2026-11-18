<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Controller\Adminhtml\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStatus;
use Magento\Framework\Controller\ResultInterface;

/**
 * "One-click upgrade": repeats the pre-flight on the server and starts the background worker. The worker takes the
 * release from the verified cache, never from anything the browser sends.
 */
class Start extends AbstractUpgrade
{
    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $service = $this->factory->create();
        $found = $service->peek();
        $data = $this->describe($service, $found);
        if (!$found['newer'] || $found['manifest'] === null) {
            $data['started'] = false;
            return $this->json->create()->setData($data);
        }
        if ($data['blocked_by']) {
            $data['started'] = false;
            return $this->json->create()->setData($data);
        }
        $service->markQueued($found['manifest']);
        exec($this->factory->backgroundCommand()); // phpcs:ignore Magento2.Security.InsecureFunction
        $data['started'] = true;
        $data['status'] = ['state' => UpgradeStatus::RUNNING] + $service->statusSummary();

        return $this->json->create()->setData($data);
    }
}
