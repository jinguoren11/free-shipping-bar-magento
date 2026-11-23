<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * While the store is not authorized, the settings page shows only the authorization card (and the groups that must stay:
 * the one-click upgrade and the "More tools" panel). Pure rule, used by the plugin on the configuration group.
 */
class SettingsVisibility
{
    private const SECTION = 'getdropflow_fsb';
    private const ALWAYS = ['authorization', 'upgrade', 'about'];

    /**
     * Should the group be shown?
     *
     * @param string $groupPath for example getdropflow_fsb/general
     * @param bool $locked
     * @return bool
     */
    public function isVisible(string $groupPath, bool $locked): bool
    {
        if (!$locked) {
            return true;
        }
        $parts = explode('/', $groupPath);
        if ($parts[0] !== self::SECTION) {
            return true; // someone else's page
        }
        return isset($parts[1]) && in_array($parts[1], self::ALWAYS, true);
    }
}
