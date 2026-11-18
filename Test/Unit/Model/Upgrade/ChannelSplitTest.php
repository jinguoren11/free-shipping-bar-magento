<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Upgrade;

use PHPUnit\Framework\TestCase;

/**
 * Everything that downloads or replaces code belongs to the download edition and must be removed from the
 * Marketplace package. Static checks; the real proof is the scan inside build/package.sh.
 */
class ChannelSplitTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../..';

    public function testPackageScriptRemovesEveryDirectOnlyPath(): void
    {
        $script = (string) file_get_contents(self::ROOT . '/build/package.sh');
        foreach (['Model/Upgrade', 'Console', 'Controller', 'etc/di.xml', 'etc/adminhtml/routes.xml',
                     'Block/Adminhtml/System/Config/UpgradeCard.php', 'view/adminhtml/web/js/upgrade-card.js'] as $p) {
            $this->assertStringContainsString($p, $script, $p . ' must be removed from the Marketplace package');
        }
        $this->assertMatchesRegularExpression('/FILES=\(Block Console Controller /', $script);
    }

    public function testTheUpdatesGroupIsInsideTheDirectOnlyMarkers(): void
    {
        $xml = (string) file_get_contents(self::ROOT . '/etc/adminhtml/system.xml');
        $this->assertMatchesRegularExpression(
            '/CHANNEL:direct-begin -->\s*<group id="upgrade".*?<\/group>\s*<!-- CHANNEL:direct-end/s',
            $xml
        );
    }

    public function testNoOtherFileReachesIntoTheUpgradeCode(): void
    {
        $allowed = ['Model/Upgrade', 'Console', 'Controller', 'Block/Adminhtml/System/Config/UpgradeCard.php',
            'Block/Adminhtml/System/Config/StarLink.php', 'Test', 'build'];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            self::ROOT,
            \FilesystemIterator::SKIP_DOTS
        ));
        foreach ($it as $file) {
            $rel = ltrim(str_replace(realpath(self::ROOT), '', (string) $file->getRealPath()), '/');
            if ($file->getExtension() !== 'php' || strpos($rel, 'dist/') === 0 || strpos($rel, 'vendor/') === 0) {
                continue;
            }
            foreach ($allowed as $a) {
                if (strpos($rel, $a) === 0) {
                    continue 2;
                }
            }
            $this->assertStringNotContainsString(
                'Model\\Upgrade',
                (string) file_get_contents((string) $file),
                $rel . ' uses direct-only code but is part of the Marketplace package'
            );
        }
    }

    /**
     * @dataProvider locales
     */
    public function testEveryUpgradeCardTextIsTranslated(string $locale): void
    {
        $php = (string) file_get_contents(self::ROOT . '/Block/Adminhtml/System/Config/UpgradeCard.php');
        preg_match_all("/__\('((?:[^'\\\\]|\\\\.)*)'\)/", $php, $m);
        $this->assertGreaterThan(25, count($m[1]));
        $have = [];
        foreach (file(self::ROOT . '/i18n/' . $locale . '.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
            $c = str_getcsv($l, ',', '"', '');
            if (count($c) >= 2 && $c[1] !== '') {
                $have[$c[0]] = true;
            }
        }
        foreach ($m[1] as $text) {
            $this->assertArrayHasKey($text, $have, $locale . ' is missing: ' . $text);
        }
    }

    public static function locales(): array
    {
        return [['en_US'], ['es_ES'], ['zh_Hans_CN']];
    }
}
