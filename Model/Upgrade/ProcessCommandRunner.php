<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Runs "php bin/magento ..." as a child process (download edition only).
 */
class ProcessCommandRunner implements CommandRunner
{
    /** @var string */
    private $phpBinary;
    /** @var string */
    private $root;

    /**
     * @param string $phpBinary
     * @param string $root Magento root folder
     */
    public function __construct(string $phpBinary, string $root)
    {
        $this->phpBinary = $phpBinary;
        $this->root = $root;
    }

    /**
     * @inheritDoc
     */
    public function magento(array $arguments): string
    {
        $command = array_merge([$this->phpBinary, '-d', 'memory_limit=-1', $this->root . '/bin/magento'], $arguments);
        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->root
        );
        if (!is_resource($process)) {
            throw new UpgradeRejected('COMMAND_FAILED', 'A Magento command could not be started.');
        }
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);
        if ($code !== 0) {
            throw new UpgradeRejected('COMMAND_FAILED', 'A Magento command failed: ' . $arguments[0]
                . ' ' . substr(trim($err !== '' ? $err : $out), 0, 300));
        }
        return $out;
    }
}
