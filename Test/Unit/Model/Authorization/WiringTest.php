<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use PHPUnit\Framework\TestCase;

/**
 * The rules that cannot be unit tested through Magento are checked in the source: what the lock touches, what it never
 * touches (cart, checkout, orders, the theme files), and that every text of the new card is translated.
 */
class WiringTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../..';

    private function read(string $rel): string
    {
        return (string) file_get_contents(self::ROOT . '/' . $rel);
    }

    public function testTheStorefrontSectionAsksTheGate(): void
    {
        $src = $this->read('CustomerData/FreeShippingBar.php');
        $this->assertStringContainsString('$this->access->isAllowed() && $this->config->flag(\'general/enabled\'', $src);
    }

    public function testTheLockOnlyHidesTheBarAndNeverHooksCartCheckoutOrOrders(): void
    {
        foreach (['etc/di.xml', 'etc/frontend/di.xml', 'etc/adminhtml/di.xml'] as $file) {
            $xml = $this->read($file);
            $this->assertDoesNotMatchRegularExpression('/Magento\\\\(Checkout|Quote|Sales|Catalog|Payment|Shipping|OfflineShipping)\\\\/', $xml, $file);
        }
        $this->assertStringNotContainsString('<plugin', $this->read('etc/di.xml'));
        $this->assertStringNotContainsString('<plugin', $this->read('etc/frontend/di.xml'));
        // the one plugin of the module is on the configuration page of this module only
        preg_match_all('/<type name="([^"]+)">\s*<plugin/', $this->read('etc/adminhtml/di.xml'), $m);
        $this->assertSame(['Magento\Config\Model\Config\Structure\Element\Group'], $m[1]);
        $this->assertFileDoesNotExist(self::ROOT . '/etc/events.xml');
        $this->assertFileDoesNotExist(self::ROOT . '/etc/frontend/events.xml');
    }

    public function testThemeFilesDoNotKnowAboutTheAuthorization(): void
    {
        $files = [];
        foreach (['view/frontend', 'Compat/Hyva/view', 'Compat/Hyva/ViewModel', 'Block/Bar.php'] as $rel) {
            $path = self::ROOT . '/' . $rel;
            if (is_file($path)) {
                $files[] = $path;
                continue;
            }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $f) {
                $files[] = (string) $f;
            }
        }
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            $text = (string) file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/Authoriz|AccessCheck|Model\\\\Access/i', $text, $file);
        }
    }

    public function testTheCronJobAndTheDirectOnlyFilesArePartOfTheDirectEditionOnly(): void
    {
        $script = $this->read('build/package.sh');
        foreach (['Model/Authorization', 'Plugin', 'etc/adminhtml/di.xml', 'Block/Adminhtml/System/Config/AuthorizeCard.php',
                     'view/adminhtml/web/js/authorize-card.js'] as $p) {
            $this->assertStringContainsString($p, $script, $p);
        }
        $this->assertStringContainsString('Cron', $script);
        $this->assertStringContainsString('RefreshAuthorization', $this->read('etc/crontab.xml'));
        $xml = $this->read('etc/adminhtml/system.xml');
        $this->assertMatchesRegularExpression('/CHANNEL:direct-begin -->\s*<group id="authorization".*?<\/group>\s*<!-- CHANNEL:direct-end/s', $xml);
    }

    public function testNoCodeOutsideTheDirectOnlyFilesReachesIntoTheAuthorizationCode(): void
    {
        $allowed = ['Model/Authorization', 'Plugin', 'Controller', 'Cron', 'Block/Adminhtml/System/Config/AuthorizeCard.php', 'Test', 'build', 'dist', 'vendor'];
        $root = (string) realpath(self::ROOT);
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $rel = ltrim(str_replace($root, '', (string) $file->getRealPath()), '/');
            if ($file->getExtension() !== 'php') {
                continue;
            }
            foreach ($allowed as $a) {
                if (strpos($rel, $a) === 0) {
                    continue 2;
                }
            }
            $this->assertStringNotContainsString('Model\\Authorization', (string) file_get_contents((string) $file), $rel);
        }
    }

    public function testTheMarketplaceEditionHasNoNetworkOrSignatureCodeOutsideTheDirectOnlyFiles(): void
    {
        $this->assertStringNotContainsString('sodium_', $this->read('Model/Access.php') . $this->read('Model/AccessCheck.php') . $this->read('CustomerData/FreeShippingBar.php'));
    }

    public function testEveryTextOfTheCardIsTranslatedAndKeepsItsPlaceholders(): void
    {
        preg_match_all("/__\\('((?:[^'\\\\]|\\\\.)*)'\\)/", $this->read('Block/Adminhtml/System/Config/AuthorizeCard.php'), $m);
        $texts = array_map(static function (string $s): string {
            return str_replace("\\'", "'", $s);
        }, $m[1]);
        $this->assertGreaterThan(30, count($texts));
        foreach (['en_US', 'es_ES', 'zh_Hans_CN'] as $locale) {
            $rows = [];
            foreach (file(self::ROOT . '/i18n/' . $locale . '.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $cols = str_getcsv($line, ',', '"', '');
                if (count($cols) >= 2) {
                    $rows[$cols[0]] = $cols[1];
                }
            }
            foreach ($texts as $text) {
                $this->assertArrayHasKey($text, $rows, $locale . ' misses: ' . $text);
                $this->assertNotSame('', $rows[$text], $locale . ': ' . $text);
                preg_match_all('/%\d/', $text, $a);
                preg_match_all('/%\d/', $rows[$text], $b);
                $this->assertSame($a[0], $b[0], $locale . ' placeholders: ' . $text);
                if ($locale !== 'en_US') {
                    $this->assertNotSame($text, $rows[$text], $locale . ' left in English: ' . $text);
                }
            }
        }
        $this->assertContains('Authorization', array_keys($rows));
    }

    public function testEveryTextTheScriptUsesComesFromThePhpTexts(): void
    {
        $js = $this->read('view/adminhtml/web/js/authorize-card.js');
        $php = $this->read('Block/Adminhtml/System/Config/AuthorizeCard.php');
        preg_match_all('/\bt\.(\w+)/', $js, $m);
        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $key) {
            $this->assertStringContainsString("'" . $key . "' =>", $php, 't.' . $key);
        }
        // every result word the controller/link service can answer has a "r_" text
        $service = $this->read('Model/Authorization/LinkService.php') . $this->read('Model/Authorization/StatusService.php');
        preg_match_all("/const [A-Z_]+ = '([a-z_]+)';/", $service, $c);
        foreach (array_unique($c[1]) as $word) {
            if (in_array($word, ['waiting', 'none'], true)) {
                continue;
            }
            $this->assertStringContainsString("'r_" . $word . "' =>", $php, 'no text for result ' . $word);
        }
    }
}
