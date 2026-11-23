<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Plugin;

use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Authorization\SettingsVisibility;
use Magento\Config\Model\Config\Structure\Element\Group;

/**
 * Download edition only. Locked store: the settings page shows the authorization card (and the upgrade card and
 * "More tools"), not the settings of the bar. Only touches this module's own page.
 */
class HideSettingsWhenLocked
{
    /**
     * @var AuthorizationGate
     */
    private $gate;

    /**
     * @var SettingsVisibility
     */
    private $visibility;

    /**
     * @param AuthorizationGate $gate
     * @param SettingsVisibility $visibility
     */
    public function __construct(AuthorizationGate $gate, SettingsVisibility $visibility)
    {
        $this->gate = $gate;
        $this->visibility = $visibility;
    }

    /**
     * Hides the group while the store is locked.
     *
     * @param Group $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsVisible(Group $subject, $result)
    {
        if (!$result) {
            return $result;
        }
        try {
            return $this->visibility->isVisible((string) $subject->getPath(), $this->gate->state()->isLocked());
        } catch (\Throwable $e) {
            return $result; // never break the settings page
        }
    }
}
