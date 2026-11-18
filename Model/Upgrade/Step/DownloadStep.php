<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

use GetDropFlow\FreeShippingBar\Model\Upgrade\HttpClient;
use GetDropFlow\FreeShippingBar\Model\Upgrade\PackageInspector;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifest;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;

/**
 * Downloads the package and checks size, checksum and every path inside it. Nothing of the shop is touched.
 */
class DownloadStep implements UpgradeStep
{
    /** @var HttpClient */
    private $http;
    /** @var PackageInspector */
    private $inspector;
    /** @var ReleaseManifest */
    private $manifest;
    /** @var string */
    private $zipPath;

    /**
     * @param HttpClient $http
     * @param PackageInspector $inspector
     * @param ReleaseManifest $manifest
     * @param string $zipPath
     */
    public function __construct(
        HttpClient $http,
        PackageInspector $inspector,
        ReleaseManifest $manifest,
        string $zipPath
    ) {
        $this->http = $http;
        $this->inspector = $inspector;
        $this->manifest = $manifest;
        $this->zipPath = $zipPath;
    }

    /**
     * @inheritDoc
     */
    public function label(): string
    {
        return 'Download and check the new version';
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        $this->http->download($this->manifest->url, $this->zipPath, $this->manifest->bytes);
        $this->inspector->inspect($this->zipPath, $this->manifest);
    }

    /**
     * @inheritDoc
     */
    public function undo(): void
    {
        Files::remove($this->zipPath);
    }
}
