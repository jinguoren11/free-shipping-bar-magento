<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Controller\Adminhtml\Authorize;

use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Authorization\CardData;
use GetDropFlow\FreeShippingBar\Model\Authorization\Clock;
use GetDropFlow\FreeShippingBar\Model\Authorization\LinkService;
use GetDropFlow\FreeShippingBar\Model\Authorization\StatusService;
use GetDropFlow\FreeShippingBar\Model\Authorization\Store;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Locale\ResolverInterface;

/**
 * The one small JSON endpoint of the authorization card. Admin login, the permission of the settings page and the
 * admin form key (checked by Magento for POST) protect it. op = start | poll | cancel | check | key | disconnect.
 */
class Run extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'GetDropFlow_FreeShippingBar::config';

    /** @var JsonFactory */
    private $json;
    /** @var LinkService */
    private $link;
    /** @var StatusService */
    private $status;
    /** @var Store */
    private $store;
    /** @var AuthorizationGate */
    private $gate;
    /** @var CardData */
    private $card;
    /** @var Clock */
    private $clock;
    /** @var ResolverInterface */
    private $locale;

    /**
     * @param Context $context
     * @param JsonFactory $json
     * @param LinkService $link
     * @param StatusService $status
     * @param Store $store
     * @param AuthorizationGate $gate
     * @param CardData $card
     * @param Clock $clock
     * @param ResolverInterface $locale
     */
    public function __construct(
        Context $context,
        JsonFactory $json,
        LinkService $link,
        StatusService $status,
        Store $store,
        AuthorizationGate $gate,
        CardData $card,
        Clock $clock,
        ResolverInterface $locale
    ) {
        parent::__construct($context);
        $this->json = $json;
        $this->link = $link;
        $this->status = $status;
        $this->store = $store;
        $this->gate = $gate;
        $this->card = $card;
        $this->clock = $clock;
        $this->locale = $locale;
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        $op = (string) $this->getRequest()->getParam('op');
        $result = ['result' => 'none'];
        switch ($op) {
            case 'start':
                $result = $this->link->begin(strtolower((string) strstr($this->locale->getLocale() . '_', '_', true)));
                break;
            case 'poll':
                $result = $this->link->poll();
                break;
            case 'cancel':
                $this->link->cancel();
                break;
            case 'check':
                $result = ['result' => $this->status->refresh()];
                break;
            case 'key':
                $result = ['result' => $this->link->saveTypedKey((string) $this->getRequest()->getParam('license_key'))];
                break;
            case 'disconnect':
                $this->store->forget();
                break;
        }
        $this->gate->forget();
        $result['card'] = $this->describe();
        return $this->json->create()->setData($result);
    }

    /**
     * What the card shows now.
     *
     * @return array
     */
    private function describe(): array
    {
        return $this->card->build(
            $this->gate->state(),
            $this->store->credential() !== '',
            $this->store->record(),
            $this->clock->now()
        );
    }
}
