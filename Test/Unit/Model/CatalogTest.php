<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use GetDropFlow\FreeShippingBar\Model\MoreTools\Catalog;
use PHPUnit\Framework\TestCase;

class CatalogTest extends TestCase
{
    public function testEveryEntryIsCompleteAndSafe(): void
    {
        $items = (new Catalog())->getItems();
        $this->assertNotEmpty($items);
        foreach ($items as $item) {
            $this->assertNotSame('', trim($item['name']));
            $this->assertNotSame('', trim($item['description']));
            $this->assertStringStartsWith('https://', $item['url']);
            $this->assertNotFalse(parse_url($item['url'], PHP_URL_HOST));
            // Marketplace documentation rule: no purchase wording in the panel.
            $this->assertDoesNotMatchRegularExpression('/\b(buy|purchase|subscribe|price|discount|coupon)\b/i', $item['description']);
        }
    }

    public function testNoNetworkCodeInModule(): void
    {
        $root = dirname(__DIR__, 3);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = $file->getPathname();
            if (!preg_match('/\.(php|js|phtml)$/', $path) || preg_match('#/(Test|vendor|dist|\.git|Upgrade)/#', $path)) {
                continue;
            }
            $code = (string) file_get_contents($path);
            $this->assertDoesNotMatchRegularExpression(
                '/\b(curl_init|file_get_contents\s*\(\s*[\'"]https?:|fsockopen|Curl\\\\Client|\$\.ajax|XMLHttpRequest|fetch\s*\()/i',
                $code,
                'Network call found in ' . $path
            );
        }
    }
}
