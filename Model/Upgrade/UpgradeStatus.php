<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Progress of an upgrade, kept in one small JSON file so the settings page can show it while a separate
 * background process does the work. States: idle, running, done, rolled_back, needs_manual.
 */
class UpgradeStatus
{
    public const IDLE = 'idle';
    public const RUNNING = 'running';
    public const DONE = 'done';
    public const ROLLED_BACK = 'rolled_back';
    public const NEEDS_MANUAL = 'needs_manual';
    /** A running upgrade that has not written anything for this long is treated as dead (compiling is slow). */
    public const STALE_SECONDS = 3600;

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
     * @param int|null $now current time (for tests)
     * @return array
     */
    public function read(?int $now = null): array
    {
        $now = $now ?? time();
        $data = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true) : null;
        if (!is_array($data) || !isset($data['state'])) {
            return ['state' => self::IDLE, 'step' => 0, 'total' => 0, 'label' => '', 'message' => '', 'time' => 0];
        }
        if ($data['state'] === self::RUNNING && $now - (int) ($data['time'] ?? 0) > self::STALE_SECONDS) {
            $data['stale'] = true;
        }
        return $data;
    }

    /**
     * Tells whether an upgrade is running right now (not dead).
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
     * Writes the status.
     *
     * @param string $state
     * @param int $step
     * @param int $total
     * @param string $label
     * @param string $message plain explanation for the person
     * @param array $extra more fields (for example "from", "to")
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
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, (string) json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }
}
