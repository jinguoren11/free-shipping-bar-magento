<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Block\Adminhtml\System\Config;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseKey;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ServiceFactory;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * The "Updates" card on the settings page (download edition only): current version, "Check for updates",
 * "Upgrade now", progress and result. It contacts nobody when the page opens; only the button does.
 */
class UpgradeCard extends Field
{
    /** @var ServiceFactory */
    private $factory;

    /**
     * @param Context $context
     * @param ServiceFactory $factory
     * @param array $data
     */
    public function __construct(Context $context, ServiceFactory $factory, array $data = [])
    {
        parent::__construct($context, $data);
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function render(AbstractElement $element): string
    {
        $config = [
            'checkUrl' => $this->getUrl('getdropflow_fsb/upgrade/check'),
            'startUrl' => $this->getUrl('getdropflow_fsb/upgrade/start'),
            'statusUrl' => $this->getUrl('getdropflow_fsb/upgrade/status'),
            'formKey' => $this->getFormKey(),
            'enabled' => ReleaseKey::PUBLIC_KEY_BASE64 !== '',
            'current' => $this->currentVersion(),
            'texts' => $this->texts(),
        ];
        return '<tr id="row_' . $this->escapeHtmlAttr($element->getHtmlId()) . '"><td colspan="4">'
            . '<div id="gdf-fsb-upgrade" class="gdf-fsb-upgrade" aria-live="polite"></div>'
            . '<script type="text/x-magento-init">' . (string) json_encode([
                '#gdf-fsb-upgrade' => ['GetDropFlow_FreeShippingBar/js/upgrade-card' => $config],
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES) . '</script>'
            . '</td></tr>';
    }

    /**
     * Installed version, read from the module's own composer.json (no network).
     *
     * @return string
     */
    private function currentVersion(): string
    {
        try {
            return $this->factory->create()->currentVersion();
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * All texts the card shows, translated here so the browser code holds no language of its own.
     *
     * @return array
     */
    private function texts(): array
    {
        return [
            'title' => (string) __('Updates'),
            'current' => (string) __('Installed version:'),
            'latest' => (string) __('Newest version:'),
            'check' => (string) __('Check for updates'),
            'checking' => (string) __('Checking...'),
            'upgrade' => (string) __('Upgrade now (one click)'),
            'upToDate' => (string) __('You have the newest version.'),
            'notChecked' => (string) __('Nothing is contacted until you press "Check for updates".'),
            'notes' => (string) __('What is new:'),
            'off' => (string) __('One-click upgrade is not switched on in this version. See the user guide to upgrade by hand.'),
            'working' => (string) __('Upgrading. Please keep this page open and do not switch anything off.'),
            'step' => (string) __('Step %1 of %2:'),
            'done' => (string) __('Done. The module was upgraded to version %1. Reload this page.'),
            'rolledBack' => (string) __('The upgrade did not work. Your previous version was put back.'),
            'needsManual' => (string) __('The upgrade stopped and the restore did not finish. The shop stays in maintenance mode: follow the restore steps in the user guide.'),
            'manual' => (string) __('You can still upgrade by hand: see "Updating the module" in the user guide.'),
            'confirm' => (string) __('Upgrade now? The current version is backed up and put back automatically if anything fails. The shop is in maintenance mode for a few minutes.'),
            'errors' => [
                'NO_PUBLIC_KEY' => (string) __('One-click upgrade is not switched on in this version.'),
                'BAD_SIGNATURE' => (string) __('The version information is not signed by the author. Nothing was changed.'),
                'BAD_MANIFEST' => (string) __('The version information could not be read.'),
                'BAD_URL' => (string) __('The download address is not an allowed address.'),
                'DOWNLOAD_FAILED' => (string) __('The download did not work. Try again later.'),
                'NOT_IN_APP_CODE' => (string) __('This module was installed with Composer: upgrade it with Composer.'),
                'NOT_WRITABLE' => (string) __('The module folder is not writable by the web server.'),
                'NO_ZIP' => (string) __('The PHP zip extension is missing on this server.'),
                'CANNOT_RUN_COMMANDS' => (string) __('This server does not allow the upgrade to run commands.'),
                'LOW_DISK' => (string) __('There is not enough free disk space (200 MB needed).'),
                'ALREADY_RUNNING' => (string) __('An upgrade is already running.'),
                'NOT_NEWER' => (string) __('The newest version is not newer than yours.'),
                'TOO_OLD' => (string) __('Your version is too old for a one-click upgrade. Upgrade by hand.'),
                'STEP_FAILED' => (string) __('A step of the upgrade failed.'),
                'COMMAND_FAILED' => (string) __('A Magento command failed during the upgrade.'),
                'VERIFY_FAILED' => (string) __('The check after the upgrade failed.'),
                'INTERRUPTED' => (string) __('The upgrade was interrupted.'),
            ],
        ];
    }
}
