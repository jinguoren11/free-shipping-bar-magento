<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

use Magento\Framework\Shell;

/**
 * Runs "php bin/magento ..." through Magento's own Shell service (download edition only). Every argument is escaped
 * by Magento; a failing command (non-zero exit) becomes an UpgradeRejected with the start of its output.
 */
class ShellCommandRunner implements CommandRunner
{
    /** @var Shell */
    private $shell;
    /** @var string */
    private $phpBinary;
    /** @var string */
    private $root;

    /**
     * @param Shell $shell
     * @param string $phpBinary php program that matches the PHP of the web server
     * @param string $root Magento root folder
     */
    public function __construct(Shell $shell, string $phpBinary, string $root)
    {
        $this->shell = $shell;
        $this->phpBinary = $phpBinary;
        $this->root = $root;
    }

    /**
     * @inheritDoc
     */
    public function magento(array $arguments): string
    {
        $format = '%s -d memory_limit=-1 %s' . str_repeat(' %s', count($arguments));
        try {
            return (string) $this->shell->execute(
                $format,
                array_merge([$this->phpBinary, $this->root . '/bin/magento'], array_values($arguments))
            );
        } catch (\Throwable $e) {
            throw new UpgradeRejected(
                'COMMAND_FAILED',
                'A Magento command failed: ' . ($arguments[0] ?? '') . ' ' . substr(trim($e->getMessage()), 0, 300)
            );
        }
    }
}
