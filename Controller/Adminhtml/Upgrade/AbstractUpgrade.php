<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Controller\Adminhtml\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ServiceFactory;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeService;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStatus;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Base of the three small JSON endpoints used by the upgrade card. Admin login, the permission of the settings
 * page and the admin form key (checked by Magento for POST) protect them.
 */
abstract class AbstractUpgrade extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'GetDropFlow_FreeShippingBar::config';

    /** @var JsonFactory */
    protected $json;
    /** @var ServiceFactory */
    protected $factory;

    /**
     * @param Context $context
     * @param JsonFactory $json
     * @param ServiceFactory $factory
     */
    public function __construct(Context $context, JsonFactory $json, ServiceFactory $factory)
    {
        parent::__construct($context);
        $this->json = $json;
        $this->factory = $factory;
    }

    /**
     * Describes the current situation for the card: versions, newest release, why the upgrade cannot run, status.
     *
     * @param UpgradeService $service
     * @param array $found result of check() or peek()
     * @return array
     */
    protected function describe(UpgradeService $service, array $found): array
    {
        $manifest = $found['manifest'];
        $reasons = [];
        if ($manifest !== null && $found['newer']) {
            $reasons = $service->preflight($manifest, $this->factory->serverFacts());
        }
        return [
            'current' => $service->currentVersion(),
            'latest' => $manifest !== null ? $manifest->version : '',
            'newer' => (bool) $found['newer'],
            'notes' => $manifest !== null ? $manifest->notes : [],
            'error' => $found['error'],
            'checked_at' => $found['checked_at'],
            'blocked_by' => $reasons,
            'status' => $service->statusSummary(),
        ];
    }

    /**
     * Status values that mean "finished" (the card stops polling).
     *
     * @return string[]
     */
    protected function finalStates(): array
    {
        return [UpgradeStatus::DONE, UpgradeStatus::ROLLED_BACK, UpgradeStatus::NEEDS_MANUAL, UpgradeStatus::IDLE];
    }
}
