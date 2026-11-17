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
     * Checks the size, checksum and content of a downloaded package before it is unpacked.
     *
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
     * Checks every entry of the zip and the version declared inside it.
     *
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
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string) $stat['name'] : '';
            $this->checkName($name);
            $this->checkNotLink($zip, $i);
            $total += (int) $stat['size'];
            if ($total > self::MAX_UNPACKED_BYTES) {
                throw new UpgradeRejected('BAD_ZIP', 'The package is too big when unpacked.');
            }
            $hasModule = $hasModule || $name === self::TOP_FOLDER . '/etc/module.xml';
        }
        if (!$hasModule) {
            throw new UpgradeRejected('BAD_ZIP', 'The package holds no module.');
        }
        $composer = $zip->getFromName(self::TOP_FOLDER . '/composer.json');
        $data = is_string($composer) ? json_decode($composer, true) : null;
        if (!is_array($data) || ($data['version'] ?? null) !== $version) {
            throw new UpgradeRejected(
                'VERSION_MISMATCH',
                'The version inside the package is not the announced version.'
            );
        }
    }

    /**
     * Refuses unsafe or foreign paths inside the zip.
     *
     * @param string $name entry name inside the zip
     * @return void
     * @throws UpgradeRejected
     */
    private function checkName(string $name): void
    {
        $unsafe = $name === ''
            || strpos($name, "\0") !== false
            || strpos($name, '\\') !== false
            || $name[0] === '/'
            || preg_match('/(^|\/)\.\.(\/|$)/', $name) === 1
            || preg_match('/^[A-Za-z]:/', $name) === 1;
        if ($unsafe) {
            throw new UpgradeRejected('UNSAFE_PATH', 'The package holds an unsafe path.');
        }
        $top = self::TOP_FOLDER . '/';
        if ($name !== $top && strpos($name, $top) !== 0) {
            throw new UpgradeRejected('UNSAFE_PATH', 'The package holds a file outside its own folder.');
        }
    }

    /**
     * Refuses symbolic links inside the zip.
     *
     * @param \ZipArchive $zip
     * @param int $index
     * @return void
     * @throws UpgradeRejected
     */
    private function checkNotLink(\ZipArchive $zip, int $index): void
    {
        $os = 0;
        $attr = 0;
        if ($zip->getExternalAttributesIndex($index, $os, $attr) && $os === \ZipArchive::OPSYS_UNIX
            && ((($attr >> 16) & 0170000) === 0120000)) {
            throw new UpgradeRejected('UNSAFE_PATH', 'The package holds a link.');
        }
    }
}
