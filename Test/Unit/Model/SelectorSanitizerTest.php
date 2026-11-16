<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model;

use GetDropFlow\FreeShippingBar\Model\SelectorSanitizer;
use PHPUnit\Framework\TestCase;

class SelectorSanitizerTest extends TestCase
{
    /**
     * @dataProvider selectors
     */
    public function testSelector(string $in, string $out): void
    {
        $this->assertSame($out, (new SelectorSanitizer())->selector($in));
    }

    public static function selectors(): array
    {
        return [
            'empty' => ['', ''],
            'spaces only' => ['   ', ''],
            'class' => ['.page-title-wrapper', '.page-title-wrapper'],
            'id with trim' => ['  #maincontent ', '#maincontent'],
            'child combinator' => ['.cart-summary > .title', '.cart-summary > .title'],
            'attribute' => ['[data-role="minicart"]', '[data-role="minicart"]'],
            'control chars removed' => ["#a\n#b", '#a#b'],
            'html is refused' => ['<script>alert(1)</script>', ''],
            'css block is refused' => ['a{color:red}', ''],
            'semicolon is refused' => ['a;b', ''],
            'backslash is refused' => ['a\\b', ''],
            'too long' => [str_repeat('a', 201), ''],
        ];
    }

    public function testPlacement(): void
    {
        $s = new SelectorSanitizer();
        $this->assertSame('before', $s->placement(' Before '));
        $this->assertSame('prepend', $s->placement('prepend'));
        $this->assertSame('after', $s->placement('nonsense'));
        $this->assertSame('after', $s->placement(''));
    }
}
