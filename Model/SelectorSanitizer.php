<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model;

/**
 * Cleans the "custom mount position" CSS selector typed by the merchant, and the placement word.
 * The selector is only ever handed to document.querySelector() in the browser, never executed or echoed as HTML.
 */
class SelectorSanitizer
{
    public const MAX_LENGTH = 200;
    public const PLACEMENTS = ['before', 'after', 'prepend', 'append'];
    public const DEFAULT_PLACEMENT = 'after';

    /**
     * @param string $selector
     * @return string empty when the value is not usable
     */
    public function selector(string $selector): string
    {
        $selector = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $selector));
        if ($selector === '' || strlen($selector) > self::MAX_LENGTH || preg_match('/[<{};`\\\\]/', $selector) === 1) {
            return '';
        }
        return $selector;
    }

    /**
     * @param string $placement
     * @return string
     */
    public function placement(string $placement): string
    {
        $placement = strtolower(trim($placement));
        return in_array($placement, self::PLACEMENTS, true) ? $placement : self::DEFAULT_PLACEMENT;
    }
}
