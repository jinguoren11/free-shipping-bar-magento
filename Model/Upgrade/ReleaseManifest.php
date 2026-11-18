<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * One release as described by latest.json (attached to every GitHub Release as manifest.json).
 * Signed with Ed25519 over the canonical form of all fields except "signature".
 */
class ReleaseManifest
{
    public const FORMAT = 1;
    public const REPO = 'jinguoren11/free-shipping-bar-magento';
    public const DEFAULT_URL_PREFIX = 'https://github.com/' . self::REPO . '/releases/download/';
    public const LATEST_RELEASE_API = 'https://api.github.com/repos/' . self::REPO . '/releases/latest';
    public const MANIFEST_ASSET = 'manifest.json';
    public const MAX_BYTES = 30 * 1024 * 1024;

    /** @var string */
    public $version;
    /** @var string */
    public $url;
    /** @var string */
    public $sha256;
    /** @var int */
    public $bytes;
    /** @var string|null */
    public $minFrom;
    /** @var array */
    public $notes;
    /** @var string the signed text this was read from (kept so a cached copy can be verified again) */
    public $rawJson;

    /**
     * @param string $version
     * @param string $url
     * @param string $sha256
     * @param int $bytes
     * @param string|null $minFrom
     * @param array $notes language code => text
     * @param string $rawJson
     */
    public function __construct(
        string $version,
        string $url,
        string $sha256,
        int $bytes,
        ?string $minFrom,
        array $notes,
        string $rawJson = ''
    ) {
        $this->version = $version;
        $this->url = $url;
        $this->sha256 = $sha256;
        $this->bytes = $bytes;
        $this->minFrom = $minFrom;
        $this->notes = $notes;
        $this->rawJson = $rawJson;
    }
}
