<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Checks a downloaded upgrade package before anything is unpacked: size, sha256, and every path inside the zip.
 */
class PackageInspector
{
    public const MAX_ENTRIES = 3000;
    public const MAX_UNPACKED_BYTES = 60 * 1024 * 1024;
    public const TOP_FOLDER = 'FreeShippingBar';

    /**
     * @param string $zipPath
     * @param ReleaseManifest $manifest
     * @return void
     * @throws UpgradeRejected
     */
    public function inspect(string $zipPath, ReleaseManifest $manifest): void
    {
        clearstatcache(true, $zipPath);
        if (!is_file($zipPath) || filesize($zipPath) !== $manifest->bytes) {
            throw new UpgradeRejected('BAD_SIZE', 'The downloaded file has the wrong size.');
        }
        if (!hash_equals($manifest->sha256, (string) hash_file('sha256', $zipPath))) {
            throw new UpgradeRejected('BAD_CHECKSUM', 'The downloaded file does not match its checksum.');
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CHECKCONS) !== true) {
            throw new UpgradeRejected('BAD_ZIP', 'The downloaded file is not a valid zip file.');
        }
        try {
            $this->checkEntries($zip, $manifest->version);
        } finally {
            $zip->close();
        }
    }

    /**
     * @param \ZipArchive $zip
     * @param string $version
     * @return void
     * @throws UpgradeRejected
     */
    private function checkEntries(\ZipArchive $zip, string $version): void
    {
        if ($zip->numFiles < 1 || $zip->numFiles > self::MAX_ENTRIES) {
            throw new UpgradeRejected('BAD_ZIP', 'The package has a wrong number of files.');
        }
        $total = 0;
        $hasModule = false;
        $top = self::TOP_FOLDER . '/';
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string) $stat['name'] : '';
            if ($name === '' || strpos($name, "\0") !== false || strpos($name, '\\') !== false
                || $name[0] === '/' || preg_match('/(^|\/)\.\.(\/|$)/', $name) === 1 || preg_match('/^[A-Za-z]:/', $name) === 1) {
                throw new UpgradeRejected('UNSAFE_PATH', 'The package holds an unsafe path.');
            }
            if ($name !== $top && strpos($name, $top) !== 0) {
                throw new UpgradeRejected('UNSAFE_PATH', 'The package holds a file outside its own folder.');
            }
            $os = 0;
            $attr = 0;
            if ($zip->getExternalAttributesIndex($i, $os, $attr) && $os === \ZipArchive::OPSYS_UNIX
                && ((($attr >> 16) & 0170000) === 0120000)) {
                throw new UpgradeRejected('UNSAFE_PATH', 'The package holds a link.');
            }
            $total += (int) $stat['size'];
            if ($total > self::MAX_UNPACKED_BYTES) {
                throw new UpgradeRejected('BAD_ZIP', 'The package is too big when unpacked.');
            }
            if ($name === $top . 'etc/module.xml') {
                $hasModule = true;
            }
        }
        if (!$hasModule) {
            throw new UpgradeRejected('BAD_ZIP', 'The package holds no module.');
        }
        $composer = $zip->getFromName($top . 'composer.json');
        $data = is_string($composer) ? json_decode($composer, true) : null;
        if (!is_array($data) || ($data['version'] ?? null) !== $version) {
            throw new UpgradeRejected('VERSION_MISMATCH', 'The version inside the package is not the announced version.');
        }
    }
}
