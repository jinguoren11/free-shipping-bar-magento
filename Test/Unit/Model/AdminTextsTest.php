<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use PHPUnit\Framework\TestCase;

/**
 * Regression test for P2: Magento treats a raw {{name}} in an admin text as a template directive and removes it,
 * so the Messages hint showed "Placeholders: = amount still missing". The braces must be written as HTML entities,
 * in the source text and in every translation.
 */
class AdminTextsTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../..';

    public function testNoRawDoubleBracesInAnyAdminText(): void
    {
        $xml = (string) file_get_contents(self::ROOT . '/etc/adminhtml/system.xml');
        $sx = simplexml_load_string($xml);
        $this->assertNotFalse($sx);
        foreach ($sx->xpath('//comment | //label') as $node) {
            $this->assertStringNotContainsString('{{', (string) $node, 'raw {{ in: ' . trim((string) $node));
        }
    }

    public function testMessagesHintShowsBothPlaceholders(): void
    {
        $sx = simplexml_load_string((string) file_get_contents(self::ROOT . '/etc/adminhtml/system.xml'));
        $comment = (string) $sx->xpath('//group[@id="messages"]/comment')[0];
        $this->assertStringContainsString('&#123;&#123;amount&#125;&#125;', $comment);
        $this->assertStringContainsString('&#123;&#123;threshold&#125;&#125;', $comment);
    }

    /**
     * @dataProvider locales
     */
    public function testEveryLanguageKeepsTheCommentKeyAndBothPlaceholders(string $locale): void
    {
        $sx = simplexml_load_string((string) file_get_contents(self::ROOT . '/etc/adminhtml/system.xml'));
        $source = (string) $sx->xpath('//group[@id="messages"]/comment')[0];
        $rows = [];
        foreach (file(self::ROOT . '/i18n/' . $locale . '.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $cols = str_getcsv($line, ',', '"', '');
            if (count($cols) >= 2) {
                $rows[$cols[0]] = $cols[1];
            }
        }
        $this->assertArrayHasKey($source, $rows, $locale . ' has no translation for the Messages hint');
        $this->assertStringContainsString('&#123;&#123;amount&#125;&#125;', $rows[$source], $locale);
        $this->assertStringContainsString('&#123;&#123;threshold&#125;&#125;', $rows[$source], $locale);
        $this->assertStringNotContainsString('{{', $rows[$source], $locale);
    }

    public static function locales(): array
    {
        return [['en_US'], ['es_ES'], ['zh_Hans_CN']];
    }

    /**
     * @dataProvider adminLocales
     */
    public function testEveryAdminLabelAndCommentIsTranslated(string $locale): void
    {
        $sx = simplexml_load_string((string) file_get_contents(self::ROOT . '/etc/adminhtml/system.xml'));
        $have = [];
        foreach (file(self::ROOT . '/i18n/' . $locale . '.csv', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $cols = str_getcsv($line, ',', '"', '');
            if (count($cols) >= 2 && $cols[1] !== '') {
                $have[$cols[0]] = true;
            }
        }
        foreach ($sx->xpath('//label | //comment') as $node) {
            $text = trim((string) $node);
            if ($text !== '') {
                $this->assertArrayHasKey($text, $have, $locale . ' is missing: ' . substr($text, 0, 60));
            }
        }
    }

    public static function adminLocales(): array
    {
        return [['es_ES'], ['zh_Hans_CN']];
    }

    /**
     * Regression test for X1 (phpcs Magento2.Security.XssTemplate): every echo in a template is escaped at the
     * output itself, never through a variable that was escaped earlier.
     */
    public function testTemplatesEscapeAtTheOutput(): void
    {
        $files = array_merge(
            glob(self::ROOT . '/view/*/templates/*.phtml') ?: [],
            glob(self::ROOT . '/Compat/Hyva/view/*/templates/*.phtml') ?: []
        );
        $this->assertNotEmpty($files);
        foreach ($files as $file) {
            preg_match_all('/<\?=(.*?)\?>/s', (string) file_get_contents($file), $m);
            foreach ($m[1] as $expr) {
                $this->assertMatchesRegularExpression(
                    '/escape(Html|HtmlAttr|Url|Js)\(|@noEscape/',
                    $expr,
                    basename($file) . ': unescaped output ' . trim($expr)
                );
            }
        }
    }
}
