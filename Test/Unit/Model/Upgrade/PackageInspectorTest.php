<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\PackageInspector;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifest;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use PHPUnit\Framework\TestCase;

class PackageInspectorTest extends TestCase
{
    /** @var string */
    private $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fsb-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        rmdir($this->dir);
    }

    private function makeZip(array $files, string $name = 'p.zip'): string
    {
        $path = $this->dir . '/' . $name;
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        foreach ($files as $n => $content) {
            $zip->addFromString($n, $content);
        }
        $zip->close();
        return $path;
    }

    private function good(): array
    {
        return [
            'FreeShippingBar/etc/module.xml' => '<config/>',
            'FreeShippingBar/composer.json' => '{"version":"1.0.1"}',
            'FreeShippingBar/registration.php' => '<?php',
        ];
    }

    private function manifestFor(string $zip, string $version = '1.0.1'): ReleaseManifest
    {
        return new ReleaseManifest($version, 'https://github.com/jinguoren11/free-shipping-bar-magento/releases/download/v1.0.1/x.zip', hash_file('sha256', $zip), (int) filesize($zip), null, []);
    }

    private function assertRejected(string $reason, string $zip, ?ReleaseManifest $m = null): void
    {
        try {
            (new PackageInspector())->inspect($zip, $m ?? $this->manifestFor($zip));
            $this->fail('expected ' . $reason);
        } catch (UpgradeRejected $e) {
            $this->assertSame($reason, $e->reason);
        }
    }

    public function testGoodPackagePasses(): void
    {
        $zip = $this->makeZip($this->good());
        (new PackageInspector())->inspect($zip, $this->manifestFor($zip));
        $this->addToAssertionCount(1);
    }

    public function testWrongChecksumAndSize(): void
    {
        $zip = $this->makeZip($this->good());
        $m = $this->manifestFor($zip);
        $badSha = new ReleaseManifest('1.0.1', $m->url, str_repeat('0', 64), $m->bytes, null, []);
        $this->assertRejected('BAD_CHECKSUM', $zip, $badSha);
        $badSize = new ReleaseManifest('1.0.1', $m->url, $m->sha256, $m->bytes + 1, null, []);
        $this->assertRejected('BAD_SIZE', $zip, $badSize);
    }

    public function testTamperedFileAfterSigningIsCaught(): void
    {
        $zip = $this->makeZip($this->good());
        $m = $this->manifestFor($zip);
        file_put_contents($zip, file_get_contents($zip) . 'x');
        $this->assertRejected('BAD_SIZE', $zip, $m);
    }

    public function testUnsafePaths(): void
    {
        foreach (['FreeShippingBar/../evil.php', '/etc/passwd', 'FreeShippingBar/a\\b.php', 'Other/x.php', 'C:/x.php'] as $bad) {
            $zip = $this->makeZip($this->good() + [$bad => 'x'], 'bad.zip');
            $this->assertRejected('UNSAFE_PATH', $zip);
            unlink($zip);
        }
    }

    public function testSymlinkIsRefused(): void
    {
        $path = $this->dir . '/link.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        foreach ($this->good() as $n => $c) {
            $zip->addFromString($n, $c);
        }
        $zip->addFromString('FreeShippingBar/link', '/etc/passwd');
        $zip->setExternalAttributesName('FreeShippingBar/link', \ZipArchive::OPSYS_UNIX, 0120777 << 16);
        $zip->close();
        $this->assertRejected('UNSAFE_PATH', $path);
    }

    public function testNotAZipAndNoModuleAndVersionMismatch(): void
    {
        $notZip = $this->dir . '/n.zip';
        file_put_contents($notZip, 'hello');
        $this->assertRejected('BAD_ZIP', $notZip);

        $this->assertRejected('BAD_ZIP', $this->makeZip(['FreeShippingBar/readme.txt' => 'x'], 'nomod.zip'));

        $zip = $this->makeZip($this->good(), 'ver.zip');
        $this->assertRejected('VERSION_MISMATCH', $zip, $this->manifestFor($zip, '2.0.0'));
    }
}
