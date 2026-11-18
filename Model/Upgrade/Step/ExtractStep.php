<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\PackageInspector;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Unpacks the already checked package into the work folder (not into the shop yet).
 */
class ExtractStep implements UpgradeStep
{
    /** @var string */
    private $zipPath;
    /** @var string */
    private $targetDir;

    /**
     * @param string $zipPath
     * @param string $targetDir work folder that will hold FreeShippingBar/
     */
    public function __construct(string $zipPath, string $targetDir)
    {
        $this->zipPath = $zipPath;
        $this->targetDir = $targetDir;
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return 'Unpack the new version';
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        Files::removeTree($this->targetDir);
        mkdir($this->targetDir, 0775, true);
        $zip = new \ZipArchive();
        if ($zip->open($this->zipPath) !== true || !$zip->extractTo($this->targetDir)) {
            throw new UpgradeRejected('EXTRACT_FAILED', 'The new version could not be unpacked.');
        }
        $zip->close();
        if (!is_file($this->targetDir . '/' . PackageInspector::TOP_FOLDER . '/etc/module.xml')) {
            throw new UpgradeRejected('EXTRACT_FAILED', 'The unpacked version is incomplete.');
        }
    }

    /**
     * @inheritDoc
     */
    public function undo(): void
    {
        Files::removeTree($this->targetDir);
    }
}
