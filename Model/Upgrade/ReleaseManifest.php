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

    /**
     * @param string $version
     * @param string $url
     * @param string $sha256
     * @param int $bytes
     * @param string|null $minFrom
     * @param array $notes language code => text
     */
    public function __construct(
        string $version,
        string $url,
        string $sha256,
        int $bytes,
        ?string $minFrom,
        array $notes
    ) {
        $this->version = $version;
        $this->url = $url;
        $this->sha256 = $sha256;
        $this->bytes = $bytes;
        $this->minFrom = $minFrom;
        $this->notes = $notes;
    }

    /**
     * Canonical text that is signed: keys sorted at every level, no escaping of slashes or unicode.
     *
     * @param array $data
     * @return string
     */
    public static function canonical(array $data): string
    {
        unset($data['signature']);
        $sort = static function (&$value) use (&$sort): void {
            if (is_array($value)) {
                ksort($value);
                foreach ($value as &$item) {
                    $sort($item);
                }
            }
        };
        $sort($data);
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Checks signature and shape. Anything wrong throws UpgradeRejected and nothing is changed.
     *
     * @param string $json
     * @param string $publicKeyBase64
     * @param string[] $allowedUrlPrefixes download address must start with one of these
     * @return self
     * @throws UpgradeRejected
     */
    public static function parse(
        string $json,
        string $publicKeyBase64,
        array $allowedUrlPrefixes = [self::DEFAULT_URL_PREFIX]
    ): self {
        if ($publicKeyBase64 === '') {
            throw new UpgradeRejected('NO_PUBLIC_KEY', 'One-click upgrade is not switched on in this version.');
        }
        $data = json_decode($json, true, 8);
        if (!is_array($data) || strlen($json) > 20000) {
            throw new UpgradeRejected('BAD_MANIFEST', 'The version information could not be read.');
        }
        self::verifySignature($data, $publicKeyBase64);
        self::checkShape($data);
        self::checkUrl((string) $data['url'], $allowedUrlPrefixes);

        return new self(
            $data['version'],
            $data['url'],
            $data['sha256'],
            $data['bytes'],
            $data['min_from'] ?? null,
            self::cleanNotes((array) ($data['notes'] ?? []))
        );
    }

    /**
     * @param array $data
     * @return void
     * @throws UpgradeRejected
     */
    private static function checkShape(array $data): void
    {
        $minFrom = $data['min_from'] ?? null;
        $problems = [
            ($data['format'] ?? null) !== self::FORMAT,
            !self::isVersionValue($data['version'] ?? null),
            !is_string($data['url'] ?? null) || strlen($data['url']) > 500,
            !is_string($data['sha256'] ?? null) || preg_match('/^[0-9a-f]{64}$/', $data['sha256']) !== 1,
            !is_int($data['bytes'] ?? null) || $data['bytes'] < 1 || $data['bytes'] > self::MAX_BYTES,
            $minFrom !== null && !self::isVersionValue($minFrom),
        ];
        if (in_array(true, $problems, true)) {
            throw new UpgradeRejected('BAD_MANIFEST', 'The version information is incomplete.');
        }
    }

    /**
     * @param string $url
     * @param string[] $allowedUrlPrefixes
     * @return void
     * @throws UpgradeRejected
     */
    private static function checkUrl(string $url, array $allowedUrlPrefixes): void
    {
        foreach ($allowedUrlPrefixes as $prefix) {
            if ($prefix !== '' && strpos($url, $prefix) === 0 && strpos($url, '..') === false) {
                return;
            }
        }
        throw new UpgradeRejected('BAD_URL', 'The download address is not an allowed address.');
    }

    /**
     * @param array $raw
     * @return array
     */
    private static function cleanNotes(array $raw): array
    {
        $notes = [];
        foreach ($raw as $lang => $text) {
            if (is_string($lang) && is_string($text) && strlen($lang) <= 10) {
                $notes[$lang] = substr($text, 0, 2000);
            }
        }
        return $notes;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    private static function isVersionValue($value): bool
    {
        return is_string($value) && self::isVersion($value);
    }

    /**
     * @param string $v
     * @return bool
     */
    public static function isVersion(string $v): bool
    {
        return preg_match('/^\d{1,4}\.\d{1,4}\.\d{1,4}$/', $v) === 1;
    }

    /**
     * Is $candidate newer than $current?
     *
     * @param string $candidate
     * @param string $current
     * @return bool
     */
    public static function isNewer(string $candidate, string $current): bool
    {
        return self::isVersion($candidate) && self::isVersion($current) && version_compare($candidate, $current, '>');
    }

    /**
     * @param array $data
     * @param string $publicKeyBase64
     * @return void
     * @throws UpgradeRejected
     */
    private static function verifySignature(array $data, string $publicKeyBase64): void
    {
        $signature = base64_decode((string) ($data['signature'] ?? ''), true);
        $key = base64_decode($publicKeyBase64, true);
        if ($signature === false || $key === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !sodium_crypto_sign_verify_detached($signature, self::canonical($data), $key)) {
            throw new UpgradeRejected(
                'BAD_SIGNATURE',
                'The version information is not signed by the author. Nothing was changed.'
            );
        }
    }
}
