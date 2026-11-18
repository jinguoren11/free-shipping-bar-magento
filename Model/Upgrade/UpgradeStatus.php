<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\Files;

/**
 * Progress of an upgrade, kept in one small JSON file so the settings page can show it while a separate
 * background process does the work.
 *
 * States: idle; queued (the page asked for an upgrade, the background process has not picked it up yet); running;
 * done; failed (stopped before anything on the shop was changed); rolled_back (something was changed and the old
 * version was put back); needs_manual.
 */
class UpgradeStatus
{
    public const IDLE = 'idle';
    public const QUEUED = 'queued';
    public const RUNNING = 'running';
    public const DONE = 'done';
    public const FAILED = 'failed';
    public const ROLLED_BACK = 'rolled_back';
    public const NEEDS_MANUAL = 'needs_manual';
    /** A running upgrade that has not written anything for this long is treated as dead (compiling is slow). */
    public const STALE_SECONDS = 3600;
    /** The background process must report within this time after the click, otherwise the start has failed. */
    public const START_TIMEOUT_SECONDS = 30;
    /** A running upgrade whose process is gone is treated as dead after this much silence. */
    public const DEAD_PROCESS_SECONDS = 30;

    /** @var string */
    private $file;

    /**
     * @param string $file path of the status file
     */
    public function __construct(string $file)
    {
        $this->file = $file;
    }

    /**
     * Reads the status; a missing or damaged file means "idle".
     *
     * A "queued" status that nobody picked up within 30 seconds is reported as "failed" (reason START_TIMEOUT), and
     * a "running" status whose process is gone (or that is silent for an hour) gets "stale" = true.
     *
     * @param int|null $now current time (for tests)
     * @return array
     */
    public function read(?int $now = null): array
    {
        $now = $now ?? time();
        $data = json_decode(Files::read($this->file), true);
        if (!is_array($data) || !isset($data['state'])) {
            return ['state' => self::IDLE, 'step' => 0, 'total' => 0, 'label' => '', 'message' => '', 'time' => 0];
        }
        $age = $now - (int) ($data['time'] ?? 0);
        if ($data['state'] === self::QUEUED && $age > self::START_TIMEOUT_SECONDS) {
            $data['state'] = self::FAILED;
            $data['reason'] = 'START_TIMEOUT';
            $data['changed'] = false;
            $data['message'] = 'The background process did not start within 30 seconds. Nothing was changed.';
        }
        if ($data['state'] === self::RUNNING
            && ($age > self::STALE_SECONDS || ($age > self::DEAD_PROCESS_SECONDS && $this->processGone($data)))
        ) {
            $data['stale'] = true;
        }
        return $data;
    }

    /**
     * Tells whether an upgrade is running right now (not dead). Used by the background process itself: a request
     * that is only queued does not count, otherwise the worker would refuse the job it was started for.
     *
     * @param int|null $now
     * @return bool
     */
    public function isRunning(?int $now = null): bool
    {
        $data = $this->read($now);
        return $data['state'] === self::RUNNING && empty($data['stale']);
    }

    /**
     * Tells whether another upgrade must not be started: one is running, or one was queued a moment ago.
     *
     * @param int|null $now
     * @return bool
     */
    public function isBusy(?int $now = null): bool
    {
        $data = $this->read($now);
        return $data['state'] === self::QUEUED || ($data['state'] === self::RUNNING && empty($data['stale']));
    }

    /**
     * Writes the status.
     *
     * @param string $state
     * @param int $step
     * @param int $total
     * @param string $label
     * @param string $message plain explanation for the person
     * @param array $extra more fields (for example "from", "to", "reason", "changed")
     * @return void
     */
    public function write(
        string $state,
        int $step = 0,
        int $total = 0,
        string $label = '',
        string $message = '',
        array $extra = []
    ): void {
        $data = array_merge($extra, [
            'state' => $state,
            'step' => $step,
            'total' => $total,
            'label' => $label,
            'message' => $message,
            'time' => time(),
        ]);
        if ($state === self::RUNNING) {
            $data['pid'] = (int) getmypid();
        }
        Files::write($this->file, (string) json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Tells whether the process that wrote a "running" status is gone (only decidable on Linux with /proc).
     *
     * @param array $data
     * @return bool
     */
    private function processGone(array $data): bool
    {
        $pid = (int) ($data['pid'] ?? 0);
        return $pid > 0 && is_dir('/proc/self') && !is_dir('/proc/' . $pid);
    }

    /**
     * Appends one line to upgrade.log next to the status file, so the real cause of a failed step is never lost.
     *
     * @param string $line
     * @return void
     */
    public function log(string $line): void
    {
        try {
            $log = dirname($this->file) . '/upgrade.log';
            Files::write($log, Files::read($log) . gmdate('Y-m-d H:i:s') . ' UTC ' . $line . "\n");
        } catch (\Throwable $e) {
            return;
        }
    }
}
