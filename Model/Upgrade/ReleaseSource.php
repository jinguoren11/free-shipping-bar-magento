<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Finds the newest release: asks the public GitHub Releases API (no token, no shop data), takes the attached
 * manifest.json and checks its signature. Only runs when the person presses "Check for updates".
 * The caller keeps the result in a cache (6 hours) and enforces the 10 minute pause between checks.
 */
class ReleaseSource
{
    private const MAX_API_BYTES = 1024 * 1024;

    /** @var HttpClient */
    private $http;
    /** @var ReleaseManifestParser */
    private $parser;

    /**
     * @param HttpClient $http
     * @param ReleaseManifestParser $parser
     */
    public function __construct(HttpClient $http, ReleaseManifestParser $parser)
    {
        $this->http = $http;
        $this->parser = $parser;
    }

    /**
     * Returns the signed manifest of the newest release.
     *
     * @param string $publicKeyBase64
     * @param string $apiUrl
     * @param string[] $allowedUrlPrefixes
     * @return ReleaseManifest
     * @throws UpgradeRejected
     */
    public function latest(
        string $publicKeyBase64,
        string $apiUrl = ReleaseManifest::LATEST_RELEASE_API,
        array $allowedUrlPrefixes = [ReleaseManifest::DEFAULT_URL_PREFIX]
    ): ReleaseManifest {
        if ($publicKeyBase64 === '') {
            throw new UpgradeRejected('NO_PUBLIC_KEY', 'One-click upgrade is not switched on in this version.');
        }
        $release = json_decode($this->http->getText($apiUrl, self::MAX_API_BYTES), true, 16);
        if (!is_array($release)) {
            throw new UpgradeRejected('BAD_MANIFEST', 'The version information could not be read.');
        }
        $manifestUrl = '';
        foreach ((array) ($release['assets'] ?? []) as $asset) {
            if (is_array($asset) && ($asset['name'] ?? '') === ReleaseManifest::MANIFEST_ASSET) {
                $manifestUrl = (string) ($asset['browser_download_url'] ?? '');
            }
        }
        if ($manifestUrl === '') {
            throw new UpgradeRejected('BAD_MANIFEST', 'The newest release has no version information.');
        }
        $json = $this->http->getText($manifestUrl, 20000);

        return $this->parser->parse($json, $publicKeyBase64, $allowedUrlPrefixes);
    }
}
