<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ChangesShop;
use GetDropFlow\FreeShippingBar\Model\Upgrade\PackageInspector;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Backup + replace: moves the whole old module folder out of app/code into the work folder (so it is never loaded
 * twice), then moves the new folder in. undo() puts the old folder back. Both folders must be on the same disk
 * so that the moves are instant renames.
 */
class SwapStep implements UpgradeStep, ChangesShop
{
    /** @var string */
    private $moduleDir;
    /** @var string */
    private $newParent;
    /** @var string */
    private $backupDir;
    /** @var bool */
    private $movedOld = false;
    /** @var bool */
    private $movedNew = false;

    /**
     * @param string $moduleDir installed module folder, e.g. app/code/GetDropFlow/FreeShippingBar
     * @param string $newParent folder that holds the unpacked FreeShippingBar/
     * @param string $backupDir where the old module folder is kept
     */
    public function __construct(string $moduleDir, string $newParent, string $backupDir)
    {
        $this->moduleDir = $moduleDir;
        $this->newParent = $newParent;
        $this->backupDir = $backupDir;
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return 'Back up the old version and put the new files in place';
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $new = $this->newParent . '/' . PackageInspector::TOP_FOLDER;
        if (!is_dir($this->moduleDir) || !is_dir($new)) {
            throw new UpgradeRejected('SWAP_FAILED', 'The module folder was not found.');
        }
        Files::removeTree($this->backupDir);
        if (!Files::move($this->moduleDir, $this->backupDir)) {
            throw new UpgradeRejected('SWAP_FAILED', 'The old version could not be moved away.');
        }
        $this->movedOld = true;
        if (!Files::move($new, $this->moduleDir)) {
            throw new UpgradeRejected('SWAP_FAILED', 'The new version could not be put in place.');
        }
        $this->movedNew = true;
    }

    /**
     * @inheritDoc
     */
    public function undo(): void
    {
        if (!$this->movedOld) {
            return;
        }
        if ($this->movedNew || is_dir($this->moduleDir)) {
            Files::removeTree($this->moduleDir);
        }
        if (!Files::move($this->backupDir, $this->moduleDir)) {
            throw new UpgradeRejected('RESTORE_FAILED', 'The old version could not be put back.');
        }
        $this->movedOld = false;
        $this->movedNew = false;
    }

    /**
     * Recovery after a killed upgrade: if the backup of the old version is still there, put it back.
     *
     * @return bool true when an old version was restored
     * @throws UpgradeRejected
     */
    public function recover(): bool
    {
        if (!is_dir($this->backupDir)) {
            return false;
        }
        Files::removeTree($this->moduleDir);
        if (!Files::move($this->backupDir, $this->moduleDir)) {
            throw new UpgradeRejected('RESTORE_FAILED', 'The old version could not be put back.');
        }
        return true;
    }
}
