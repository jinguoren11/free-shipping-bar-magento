<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Block\Adminhtml\System\Config;

use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Authorization\CardData;
use GetDropFlow\FreeShippingBar\Model\Authorization\Clock;
use GetDropFlow\FreeShippingBar\Model\Authorization\LinkService;
use GetDropFlow\FreeShippingBar\Model\Authorization\Store;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * The "Authorization" card (download edition only): one button "Log in to GetDropFlow to authorize", the state of the
 * authorization, and a folded "enter a key by hand" option. Opening the page contacts nobody; only the buttons do.
 */
class AuthorizeCard extends Field
{
    /** @var AuthorizationGate */
    private $gate;
    /** @var CardData */
    private $card;
    /** @var Store */
    private $store;
    /** @var Clock */
    private $clock;
    /** @var LinkService */
    private $link;

    /**
     * @param Context $context
     * @param AuthorizationGate $gate
     * @param CardData $card
     * @param Store $store
     * @param Clock $clock
     * @param LinkService $link
     * @param array $data
     */
    public function __construct(
        Context $context,
        AuthorizationGate $gate,
        CardData $card,
        Store $store,
        Clock $clock,
        LinkService $link,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->gate = $gate;
        $this->card = $card;
        $this->store = $store;
        $this->clock = $clock;
        $this->link = $link;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        $config = [
            'url' => $this->getUrl('getdropflow_fsb/authorize/run'),
            'formKey' => $this->getFormKey(),
            'card' => $this->card->build(
                $this->gate->state(),
                $this->store->credential() !== '',
                $this->store->record(),
                $this->clock->now()
            ),
            'pending' => $this->link->pending(),
            'texts' => $this->texts(),
        ];
        return '<tr id="row_' . $this->escapeHtmlAttr($element->getHtmlId()) . '"><td colspan="4">'
            . '<div id="gdf-fsb-authorize" class="gdf-fsb-authorize" aria-live="polite"></div>'
            . '<script type="text/x-magento-init">' . (string) json_encode([
                '#gdf-fsb-authorize' => ['GetDropFlow_FreeShippingBar/js/authorize-card' => $config],
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) . '</script>'
            . '</td></tr>';
    }

    /**
     * All texts of the card, translated here so the browser code holds no language of its own.
     *
     * @return array
     */
    private function texts(): array
    {
        // phpcs:disable Generic.Files.LineLength -- a translatable sentence must stay in one piece
        return [
            'lockedTitle' => (string) __('Authorize this store to use the free shipping bar'),
            'lockedText' => (string) __('The bar is free. To switch it on, log in to your GetDropFlow account (a free account is enough) and allow this store. Until then the bar is not shown to your customers. Your cart, checkout and orders are never affected.'),
            'login' => (string) __('Log in to GetDropFlow to authorize'),
            'privacy' => (string) __('What is shared: only the web address of this store and the e-mail of the account you log in with. Nothing else is read or sent.'),
            'authorizedTitle' => (string) __('This store is authorized'),
            'plan' => (string) __('Membership: %1'),
            'offlineText' => (string) __('The GetDropFlow website cannot be reached right now. The bar keeps working for %1 more day(s) and the check is repeated automatically.'),
            'lapsedText' => (string) __('Your paid plan ended on %1. The free membership continues, and this free bar keeps working.'),
            'rejectedText' => (string) __('The GetDropFlow website no longer knows this store (it was removed or revoked in your account). Log in again to authorize it.'),
            'checkNow' => (string) __('Check now'),
            'disconnect' => (string) __('Disconnect this store'),
            'disconnectConfirm' => (string) __('Disconnect this store from your GetDropFlow account? The bar is hidden until you authorize again.'),
            'waitingTitle' => (string) __('Waiting for you to allow this store on the GetDropFlow website'),
            'waitingText' => (string) __('A new tab was opened. Log in there, check that the code is %1 and press "Allow". This page continues by itself.'),
            'openAgain' => (string) __('Open the authorization page again'),
            'cancel' => (string) __('Cancel'),
            'advanced' => (string) __('Advanced: enter a key by hand'),
            'keyLabel' => (string) __('License key'),
            'keySave' => (string) __('Save and check'),
            'working' => (string) __('Please wait...'),
            'r_linked' => (string) __('Done. This store is authorized.'),
            'r_linked_unverified' => (string) __('The key was saved but the website could not confirm it yet. Press "Check now" in a minute.'),
            'r_denied' => (string) __('You pressed "Deny" on the website. Nothing was changed.'),
            'r_expired' => (string) __('The code expired. Press the button to start again.'),
            'r_unreachable' => (string) __('The GetDropFlow website cannot be reached from this server right now. Try again in a few minutes.'),
            'r_rate_limited' => (string) __('Too many tries. Wait a few minutes and try again.'),
            'r_failed' => (string) __('The website sent an answer this plugin cannot use. Try again, or enter a key by hand.'),
            'r_bad_key' => (string) __('This does not look like a license key. Copy the whole key again from your GetDropFlow account and paste it here.'),
            'r_key_unknown' => (string) __('The GetDropFlow website does not know this key. Check that you copied the whole key from your account, or use the log-in button above.'),
            'r_not_configured' => (string) __('This store has no usable web address (https is needed).'),
            'r_verified' => (string) __('Checked: this store is authorized.'),
            'r_rejected' => (string) __('The website says this store is not authorized.'),
            'r_no_credential' => (string) __('This store is not connected yet.'),
        ];
        // phpcs:enable Generic.Files.LineLength
    }
}
