<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

/**
 * Small file helpers shared by the steps.
 */
class Files
{
    /**
     * Deletes a folder and everything in it (links are removed, never followed).
     *
     * @param string $dir
     * @return void
     */
    public static function removeTree(string $dir): void
    {
        if (is_link($dir) || is_file($dir)) {
            unlink($dir);
            return;
        }
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $name) {
            if ($name !== '.' && $name !== '..') {
                self::removeTree($dir . '/' . $name);
            }
        }
        rmdir($dir);
    }
}
