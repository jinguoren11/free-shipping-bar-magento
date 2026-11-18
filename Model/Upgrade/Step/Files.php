<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

// phpcs:disable Magento2.Functions.DiscouragedFunction -- Download edition only. The upgrade engine must run while
// Magento's own code is being replaced and in unit tests without Magento, so all plain file work of the engine is
// kept in this one small class instead of Magento's file drivers.
// phpcs:disable Magento2.Functions.StaticFunction -- stateless helpers, nothing to inject

namespace GetDropFlow\FreeShippingBar\Model\Upgrade\Step;

/**
 * Small file helpers shared by the upgrade engine (the only place that touches the disk directly).
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

    /**
     * Deletes one file when it exists.
     *
     * @param string $file
     * @return void
     */
    public static function remove(string $file): void
    {
        if (is_file($file) || is_link($file)) {
            unlink($file);
        }
    }

    /**
     * Creates a folder (and its parents) when it is missing.
     *
     * @param string $dir
     * @return void
     */
    public static function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    /**
     * Writes a file (creating its folder), with a lock.
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public static function write(string $file, string $content): void
    {
        self::ensureDir(dirname($file));
        file_put_contents($file, $content, LOCK_EX);
    }

    /**
     * Reads a file; '' when it does not exist.
     *
     * @param string $file
     * @return string
     */
    public static function read(string $file): string
    {
        return is_file($file) ? (string) file_get_contents($file) : '';
    }

    /**
     * Renames a file or folder. Returns false (instead of a PHP warning) when that does not work.
     *
     * @param string $from
     * @param string $to
     * @return bool
     */
    public static function move(string $from, string $to): bool
    {
        set_error_handler(static function (): bool {
            return true;
        });
        $moved = rename($from, $to);
        restore_error_handler();

        return $moved;
    }

    /**
     * Free disk space in bytes; 0 when it cannot be read.
     *
     * @param string $dir
     * @return int
     */
    public static function freeBytes(string $dir): int
    {
        set_error_handler(static function (): bool {
            return true;
        });
        $free = (int) disk_free_space($dir);
        restore_error_handler();

        return $free;
    }
}
