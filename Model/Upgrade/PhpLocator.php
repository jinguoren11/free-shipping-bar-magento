<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Finds the php program for the background commands. It must have the same major.minor version as the PHP of the
 * web server: a server often has several (php8.3 for the website, a newer default php on the command line), and
 * running the shop's code on the wrong one fails at once or, worse, half way.
 */
class PhpLocator
{
    /**
     * Picks a php program.
     *
     * @param string[] $dirs folders to look in, in order
     * @param string $major PHP major version of the web server, e.g. "8"
     * @param string $minor PHP minor version of the web server, e.g. "3"
     * @param callable $isExecutable function (string $path): bool
     * @param callable $versionOf function (string $path): string, "major.minor" printed by that php program
     * @return string path, or '' when no program with the same version was found
     */
    public function locate(
        array $dirs,
        string $major,
        string $minor,
        callable $isExecutable,
        callable $versionOf
    ): string {
        $want = $major . '.' . $minor;
        foreach (['php' . $want, 'php' . $major . $minor] as $name) {
            foreach ($dirs as $dir) {
                if ($isExecutable($dir . '/' . $name)) {
                    return $dir . '/' . $name;
                }
            }
        }
        foreach ($dirs as $dir) {
            if ($isExecutable($dir . '/php') && trim((string) $versionOf($dir . '/php')) === $want) {
                return $dir . '/php';
            }
        }
        return '';
    }
}
