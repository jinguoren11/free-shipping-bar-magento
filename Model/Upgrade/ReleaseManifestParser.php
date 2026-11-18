<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Reads and checks the signed version information (latest.json / manifest.json): signature, shape, download address.
 */
class ReleaseManifestParser
{
    /**
     * Canonical text that is signed: keys sorted at every level, no escaping of slashes or unicode.
     *
     * @param array $data
     * @return string
     */
    public function canonical(array $data): string
    {
        unset($data['signature']);
        $sort = function (&$value) use (&$sort): void {
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
     * @return ReleaseManifest
     * @throws UpgradeRejected
     */
    public function parse(
        string $json,
        string $publicKeyBase64,
        array $allowedUrlPrefixes = [ReleaseManifest::DEFAULT_URL_PREFIX]
    ): ReleaseManifest {
        if ($publicKeyBase64 === '') {
            throw new UpgradeRejected('NO_PUBLIC_KEY', 'One-click upgrade is not switched on in this version.');
        }
        $data = json_decode($json, true, 8);
        if (!is_array($data) || strlen($json) > 20000) {
            throw new UpgradeRejected('BAD_MANIFEST', 'The version information could not be read.');
        }
        $this->verifySignature($data, $publicKeyBase64);
        $this->checkShape($data);
        $this->checkUrl((string) $data['url'], $allowedUrlPrefixes);

        return new ReleaseManifest(
            $data['version'],
            $data['url'],
            $data['sha256'],
            $data['bytes'],
            $data['min_from'] ?? null,
            $this->cleanNotes((array) ($data['notes'] ?? [])),
            $json
        );
    }

    /**
     * Checks that all fields of the version information are present and well formed.
     *
     * @param array $data
     * @return void
     * @throws UpgradeRejected
     */
    private function checkShape(array $data): void
    {
        $minFrom = $data['min_from'] ?? null;
        $problems = [
            ($data['format'] ?? null) !== ReleaseManifest::FORMAT,
            !$this->isVersionValue($data['version'] ?? null),
            !is_string($data['url'] ?? null) || strlen($data['url']) > 500,
            !is_string($data['sha256'] ?? null) || preg_match('/^[0-9a-f]{64}$/', $data['sha256']) !== 1,
            !is_int($data['bytes'] ?? null) || $data['bytes'] < 1 || $data['bytes'] > ReleaseManifest::MAX_BYTES,
            $minFrom !== null && !$this->isVersionValue($minFrom),
        ];
        if (in_array(true, $problems, true)) {
            throw new UpgradeRejected('BAD_MANIFEST', 'The version information is incomplete.');
        }
    }

    /**
     * Checks that the download address starts with an allowed prefix.
     *
     * @param string $url
     * @param string[] $allowedUrlPrefixes
     * @return void
     * @throws UpgradeRejected
     */
    private function checkUrl(string $url, array $allowedUrlPrefixes): void
    {
        foreach ($allowedUrlPrefixes as $prefix) {
            if ($prefix !== '' && strpos($url, $prefix) === 0 && strpos($url, '..') === false) {
                return;
            }
        }
        throw new UpgradeRejected('BAD_URL', 'The download address is not an allowed address.');
    }

    /**
     * Keeps only short text notes with a language code.
     *
     * @param array $raw
     * @return array
     */
    private function cleanNotes(array $raw): array
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
     * Tells whether a value is a version string such as 1.2.3.
     *
     * @param mixed $value
     * @return bool
     */
    private function isVersionValue($value): bool
    {
        return is_string($value) && $this->isVersion($value);
    }

    /**
     * Tells whether a text is a version string such as 1.2.3.
     *
     * @param string $v
     * @return bool
     */
    public function isVersion(string $v): bool
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
    public function isNewer(string $candidate, string $current): bool
    {
        return $this->isVersion($candidate) && $this->isVersion($current) && version_compare($candidate, $current, '>');
    }

    /**
     * Checks the Ed25519 signature of the version information.
     *
     * @param array $data
     * @param string $publicKeyBase64
     * @return void
     * @throws UpgradeRejected
     */
    private function verifySignature(array $data, string $publicKeyBase64): void
    {
        $signature = base64_decode((string) ($data['signature'] ?? ''), true);
        $key = base64_decode($publicKeyBase64, true);
        if ($signature === false || $key === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || strlen($key) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !sodium_crypto_sign_verify_detached($signature, $this->canonical($data), $key)) {
            throw new UpgradeRejected(
                'BAD_SIGNATURE',
                'The version information is not signed by the author. Nothing was changed.'
            );
        }
    }
}
