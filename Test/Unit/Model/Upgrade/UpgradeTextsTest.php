<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Upgrade;

use PHPUnit\Framework\TestCase;

/**
 * Every reason code and every step name the engine can show must have a sentence on the settings card, and that
 * sentence must be translated (es, zh). Read from the source, so a new code without a text turns this red.
 */
class UpgradeTextsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../../..';

    private function source(string $rel): string
    {
        return (string) file_get_contents(self::ROOT . '/' . $rel);
    }

    private function allEngineSource(): string
    {
        $text = '';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT . '/Model/Upgrade')) as $f) {
            if ($f->getExtension() === 'php') {
                $text .= (string) file_get_contents((string) $f);
            }
        }
        return $text . $this->source('Controller/Adminhtml/Upgrade/Start.php');
    }

    /**
     * @return string[]
     */
    private function engineCodes(): array
    {
        $src = $this->allEngineSource();
        preg_match_all("/new UpgradeRejected\(\s*'([A-Z_]+)'/", $src, $a);
        preg_match_all("/'reason' => '([A-Z_]+)'/", $src, $b);
        preg_match_all("/^\s+'([A-Z_]+)' => /m", $this->source('Model/Upgrade/Preflight.php'), $c);
        return array_values(array_unique(array_merge($a[1], $b[1], $c[1], ['START_TIMEOUT', 'BAD_SIGNATURE'])));
    }

    /**
     * @return array [code => sentence]
     */
    private function cardErrors(): array
    {
        $php = $this->source('Block/Adminhtml/System/Config/UpgradeCard.php');
        preg_match_all("/'([A-Z_]+)' => \(string\) __\('((?:[^'\\\\]|\\\\.)*)'\)/", $php, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as $row) {
            $out[$row[1]] = str_replace("\\'", "'", $row[2]);
        }
        return $out;
    }

    private function translations(string $locale): array
    {
        $have = [];
        foreach (file(self::ROOT . '/i18n/' . $locale . '.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $l) {
            $c = str_getcsv($l, ',', '"', '');
            if (count($c) >= 2 && $c[1] !== '') {
                $have[$c[0]] = $c[1];
            }
        }
        return $have;
    }

    public function testEveryReasonCodeHasACardSentence(): void
    {
        $codes = $this->engineCodes();
        $this->assertContains('BAD_CHECKSUM', $codes, 'the scan must find the codes');
        $this->assertGreaterThan(20, count($codes));
        $errors = $this->cardErrors();
        foreach ($codes as $code) {
            $this->assertArrayHasKey($code, $errors, 'no card text for reason ' . $code);
        }
    }

    public function testEveryReasonSentenceIsTranslatedAndReallyDiffers(): void
    {
        foreach (['es_ES', 'zh_Hans_CN'] as $locale) {
            $have = $this->translations($locale);
            foreach ($this->cardErrors() as $code => $text) {
                $this->assertArrayHasKey($text, $have, $locale . ' lacks the text of ' . $code);
                $this->assertNotSame($text, $have[$text], $locale . ' text of ' . $code . ' is not translated');
            }
        }
    }

    public function testEveryStepNameIsTranslated(): void
    {
        $src = $this->allEngineSource();
        preg_match_all("/return '([^']+)';\s*\}\s*\/\*\*\s*\* @inheritDoc\s*\*\/\s*public function run/", $src, $labels);
        preg_match_all("/'(Install the new version|Starting|Restoring the previous version)'/", $src, $extra);
        $names = array_unique(array_merge($labels[1], $extra[1]));
        $this->assertGreaterThanOrEqual(7, count($names), 'step names found: ' . implode(' | ', $names));
        foreach (['es_ES', 'zh_Hans_CN'] as $locale) {
            $have = $this->translations($locale);
            foreach ($names as $name) {
                $this->assertArrayHasKey($name, $have, $locale . ' lacks step name: ' . $name);
            }
        }
        $php = $this->source('Block/Adminhtml/System/Config/UpgradeCard.php');
        foreach ($names as $name) {
            $this->assertStringContainsString("'" . $name . "' => (string) __(", $php, 'card has no step: ' . $name);
        }
    }

    public function testCardScriptStaysQuietDuringMaintenanceMode(): void
    {
        $js = $this->source('view/adminhtml/web/js/upgrade-card.js');
        $this->assertStringContainsString('global: false', $js, 'a 503 must not raise the red Magento error bar');
        $this->assertStringContainsString('t.steps', $js, 'step names are shown translated');
        $this->assertStringContainsString("'queued'", $js);
        $this->assertStringContainsString("'failed'", $js);
    }
}
