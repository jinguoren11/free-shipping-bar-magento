<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\CommandsStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\DownloadStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\ExtractStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\Files;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\MaintenanceStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\SwapStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\VerifyStep;

/**
 * Puts the one-click upgrade together (download edition only): check for a new version, pre-flight, run.
 * Everything that touches the system is injected, so the logic is tested without a Magento installation.
 */
class UpgradeService
{
    public const CACHE_SECONDS = 6 * 3600;
    public const MIN_RECHECK_SECONDS = 600;

    /** @var string */
    private $workDir;
    /** @var string */
    private $moduleDir;
    /** @var UpgradeStatus */
    private $status;
    /** @var CommandRunner */
    private $commands;
    /** @var HttpClient */
    private $http;
    /** @var PackageInspector */
    private $inspector;
    /** @var ReleaseManifestParser */
    private $parser;
    /** @var ReleaseSource */
    private $source;
    /** @var Preflight */
    private $preflight;
    /** @var string */
    private $publicKey;
    /** @var string */
    private $currentVersion;
    /** @var bool */
    private $productionMode;
    /** @var string[] */
    private $locales;
    /** @var string */
    private $apiUrl;
    /** @var string[] */
    private $urlPrefixes;
    /** @var int seconds to wait between two tries to rebuild the restored shop (killed child commands may still run) */
    private $rebuildRetryDelay = 15;

    /**
     * @param string $workDir var/getdropflow_fsb_upgrade
     * @param string $moduleDir installed module folder
     * @param UpgradeStatus $status
     * @param CommandRunner $commands
     * @param HttpClient $http
     * @param PackageInspector $inspector
     * @param ReleaseManifestParser $parser
     * @param ReleaseSource $source
     * @param Preflight $preflight
     * @param string $publicKey
     * @param string $currentVersion
     * @param bool $productionMode
     * @param string[] $locales locales to deploy static files for
     * @param string $apiUrl where the newest release is announced (a local address only in tests)
     * @param string[] $urlPrefixes allowed download address starts
     */
    public function __construct(
        string $workDir,
        string $moduleDir,
        UpgradeStatus $status,
        CommandRunner $commands,
        HttpClient $http,
        PackageInspector $inspector,
        ReleaseManifestParser $parser,
        ReleaseSource $source,
        Preflight $preflight,
        string $publicKey,
        string $currentVersion,
        bool $productionMode,
        array $locales,
        string $apiUrl = ReleaseManifest::LATEST_RELEASE_API,
        array $urlPrefixes = [ReleaseManifest::DEFAULT_URL_PREFIX]
    ) {
        $this->workDir = $workDir;
        $this->moduleDir = $moduleDir;
        $this->status = $status;
        $this->commands = $commands;
        $this->http = $http;
        $this->inspector = $inspector;
        $this->parser = $parser;
        $this->source = $source;
        $this->preflight = $preflight;
        $this->publicKey = $publicKey;
        $this->currentVersion = $currentVersion;
        $this->productionMode = $productionMode;
        $this->locales = $locales;
        $this->apiUrl = $apiUrl;
        $this->urlPrefixes = $urlPrefixes;
    }

    /**
     * "Check for updates": returns the newest signed release, from the cache when it is fresh.
     * Contacts GitHub only when the cache is older than 6 hours or the person asked again after 10 minutes.
     *
     * @param bool $force person pressed the button
     * @param int|null $now
     * @return array [manifest => ?ReleaseManifest, error => ?string, checked_at => int, newer => bool]
     */
    public function check(bool $force, ?int $now = null): array
    {
        $now = $now ?? time();
        $cacheFile = $this->workDir . '/latest.json';
        $cached = json_decode(Files::read($cacheFile), true);
        $age = is_array($cached) ? $now - (int) ($cached['checked_at'] ?? 0) : PHP_INT_MAX;
        $fresh = $age < ($force ? self::MIN_RECHECK_SECONDS : self::CACHE_SECONDS);
        try {
            if ($fresh && isset($cached['manifest_json'])) {
                $manifest = $this->parser->parse(
                    (string) $cached['manifest_json'],
                    $this->publicKey,
                    $this->urlPrefixes
                );
                return $this->result($manifest, null, (int) $cached['checked_at']);
            }
            $manifest = $this->source->latest($this->publicKey, $this->apiUrl, $this->urlPrefixes);
            $this->saveCache($cacheFile, $now, $manifest);
            return $this->result($manifest, null, $now);
        } catch (UpgradeRejected $e) {
            return $this->result(null, $e->reason, $now);
        }
    }

    /**
     * What the last check found, without any network access (used when the settings page opens).
     *
     * @return array same shape as check(); manifest is null when nothing was checked yet
     */
    public function peek(): array
    {
        $cacheFile = $this->workDir . '/latest.json';
        $cached = json_decode(Files::read($cacheFile), true);
        if (is_array($cached) && isset($cached['manifest_json'])) {
            try {
                return $this->result(
                    $this->parser->parse((string) $cached['manifest_json'], $this->publicKey, $this->urlPrefixes),
                    null,
                    (int) ($cached['checked_at'] ?? 0)
                );
            } catch (UpgradeRejected $e) {
                return $this->result(null, $e->reason, 0);
            }
        }
        return $this->result(null, null, 0);
    }

    /**
     * Pre-flight reasons for a release (empty = the upgrade can run).
     *
     * @param ReleaseManifest $manifest
     * @param array $serverFacts facts only the caller can see: in_app_code, module_writable, parent_writable,
     *     can_run_commands, php_found, free_bytes
     * @param bool $asWorker true in the background process: a request that is only "queued" (the very request this
     *     process was started for) is not "already running"
     * @return string[]
     */
    public function preflight(ReleaseManifest $manifest, array $serverFacts, bool $asWorker = false): array
    {
        $facts = array_merge($serverFacts, [
            'current_version' => $this->currentVersion,
            'key_present' => $this->publicKey !== '',
            'has_zip' => class_exists(\ZipArchive::class),
            'upgrade_running' => $asWorker ? $this->status->isRunning() : $this->status->isBusy(),
        ]);
        return $this->preflight->check($facts, $manifest);
    }

    /**
     * Builds the steps in the order they run.
     *
     * @param ReleaseManifest $manifest
     * @return UpgradeStep[]
     */
    public function steps(ReleaseManifest $manifest): array
    {
        $zip = $this->workDir . '/package.zip';
        $new = $this->workDir . '/new';
        return [
            new DownloadStep($this->http, $this->inspector, $manifest, $zip),
            new ExtractStep($zip, $new),
            new MaintenanceStep($this->commands, $this->maintenanceMarker()),
            new SwapStep($this->moduleDir, $new, $this->backupDir()),
            new CommandsStep($this->commands, $this->rebuildCommands(), 'Install the new version'),
            new VerifyStep($this->commands, $this->moduleDir, $manifest->version),
        ];
    }

    /**
     * Runs the upgrade (called by the background command). Returns the final state.
     *
     * @param ReleaseManifest $manifest
     * @return string
     */
    public function execute(ReleaseManifest $manifest): string
    {
        $runner = new UpgradeRunner($this->status);
        $result = $runner->run(
            $this->steps($manifest),
            function (): void {
                foreach ($this->rebuildCommands() as $arguments) {
                    $this->commands->magento($arguments);
                }
            },
            function (): void {
                MaintenanceStep::leave($this->commands, $this->maintenanceMarker());
            },
            ['from' => $this->currentVersion, 'to' => $manifest->version]
        );
        if ($result === UpgradeStatus::DONE) {
            Files::removeTree($this->backupDir());
            $this->cleanLeftovers();
        }
        return $result;
    }

    /**
     * Step 1 of an upgrade, done by the settings page: repeat the pre-flight and write "queued". The background
     * process is started by the caller only when the returned list is empty.
     *
     * @param ReleaseManifest $manifest
     * @param array $serverFacts see preflight()
     * @return string[] reasons why it cannot start (empty = queued)
     */
    public function queue(ReleaseManifest $manifest, array $serverFacts): array
    {
        $reasons = $this->preflight($manifest, $serverFacts);
        if (!$reasons) {
            $this->markQueued($manifest);
        }
        return $reasons;
    }

    /**
     * Step 2, done by the background process (bin/magento getdropflow:fsb:upgrade): pre-flight again, then run.
     * When the pre-flight says no, the card is told (state "failed") instead of waiting for a worker that never ran.
     *
     * @param ReleaseManifest $manifest
     * @param array $serverFacts see preflight()
     * @return array [reasons => string[], state => string]
     */
    public function work(ReleaseManifest $manifest, array $serverFacts): array
    {
        $reasons = $this->preflight($manifest, $serverFacts, true);
        if ($reasons) {
            $this->status->write(
                UpgradeStatus::FAILED,
                0,
                0,
                '',
                'The upgrade could not start: ' . implode(', ', $reasons),
                ['reason' => $reasons[0], 'changed' => false, 'from' => $this->currentVersion,
                    'to' => $manifest->version]
            );
            return ['reasons' => $reasons, 'state' => UpgradeStatus::FAILED];
        }
        return ['reasons' => [], 'state' => $this->execute($manifest)];
    }

    /**
     * The background process could not be started at all: tell the card at once.
     *
     * @param string $reason reason code
     * @return void
     */
    public function markStartFailed(string $reason): void
    {
        $this->status->write(
            UpgradeStatus::FAILED,
            0,
            0,
            '',
            'The background process could not be started. Nothing was changed.',
            ['reason' => $reason, 'changed' => false]
        );
    }

    /**
     * After a killed upgrade (the process is gone, or no sign of life for an hour): put the old version back, leave
     * maintenance mode that this upgrade switched on, and remove the leftovers. Called by the settings page, by cron
     * and by "bin/magento getdropflow:fsb:upgrade --recover" (the way in when maintenance mode blocks the admin).
     *
     * @param bool $force also try again after "needs_manual" (the command line does this)
     * @return bool true when something was done
     */
    public function recoverIfDead(bool $force = false): bool
    {
        $data = $this->status->read();
        $dead = $data['state'] === UpgradeStatus::RUNNING && !empty($data['stale']);
        $retry = $force && $data['state'] === UpgradeStatus::NEEDS_MANUAL;
        if (!$dead && !$retry) {
            return false;
        }
        // shown while restoring, so that a second recovery (cron) does not start on top of this one
        $this->status->write(UpgradeStatus::RUNNING, 0, 0, 'Restoring the previous version');
        $swap = new SwapStep($this->moduleDir, $this->workDir . '/new', $this->backupDir());
        $extra = ['from' => $data['from'] ?? $this->currentVersion, 'to' => $data['to'] ?? '',
            'reason' => 'INTERRUPTED'];
        try {
            $restored = $swap->recover();
            if ($restored) {
                $this->rebuildWithRetry();
            }
            MaintenanceStep::leave($this->commands, $this->maintenanceMarker());
        } catch (\Throwable $e) {
            $this->status->log('RESTORE_FAILED ' . get_class($e) . ' ' . substr(trim($e->getMessage()), 0, 500));
            $this->status->write(
                UpgradeStatus::NEEDS_MANUAL,
                0,
                0,
                '',
                'Restore did not finish.',
                ['reason' => 'RESTORE_FAILED', 'changed' => true] + $extra
            );
            return true;
        }
        $this->cleanLeftovers();
        $this->status->write(
            $restored ? UpgradeStatus::ROLLED_BACK : UpgradeStatus::FAILED,
            0,
            0,
            '',
            $restored ? 'The upgrade was interrupted; your previous version was put back.'
                : 'The upgrade was interrupted before anything was changed.',
            ['changed' => $restored] + $extra
        );
        return true;
    }

    /**
     * Rebuilds the restored shop. A killed upgrade can leave a child command (setup:upgrade, di:compile) still
     * running for a while, which makes the first try fail: wait and try again, twice at most.
     *
     * @return void
     */
    private function rebuildWithRetry(): void
    {
        $tries = 3;
        for ($try = 1; $try <= $tries; $try++) {
            try {
                foreach ($this->rebuildCommands() as $arguments) {
                    $this->commands->magento($arguments);
                }
                return;
            } catch (\Throwable $e) {
                $this->status->log('Rebuild try ' . $try . ' failed: ' . substr(trim($e->getMessage()), 0, 500));
                if ($try === $tries) {
                    throw $e;
                }
                if ($this->rebuildRetryDelay > 0) {
                    sleep($this->rebuildRetryDelay);
                }
            }
        }
    }

    /**
     * Sets the wait between rebuild tries (tests use 0).
     *
     * @param int $seconds
     * @return void
     */
    public function setRebuildRetryDelay(int $seconds): void
    {
        $this->rebuildRetryDelay = $seconds;
    }

    /**
     * Removes what an upgrade leaves in the work folder: the unpacked new version and the downloaded zip.
     *
     * @return void
     */
    private function cleanLeftovers(): void
    {
        Files::removeTree($this->workDir . '/new');
        Files::remove($this->workDir . '/package.zip');
    }

    /**
     * Commands that make Magento use the code that is now on disk.
     *
     * @return array
     */
    public function rebuildCommands(): array
    {
        $commands = [['setup:upgrade', '--keep-generated']];
        if ($this->productionMode) {
            $commands[] = ['setup:di:compile'];
            $commands[] = array_merge(['setup:static-content:deploy', '-f'], $this->locales);
        }
        $commands[] = ['cache:flush'];
        return $commands;
    }

    /**
     * Current progress for the settings page.
     *
     * @return array
     */
    public function statusSummary(): array
    {
        return $this->status->read();
    }

    /**
     * Writes "queued" at once so that the card shows progress before the worker has started. It is not "running":
     * the worker's own pre-flight would refuse a running upgrade.
     *
     * @param ReleaseManifest $manifest
     * @return void
     */
    public function markQueued(ReleaseManifest $manifest): void
    {
        $this->status->write(
            UpgradeStatus::QUEUED,
            0,
            count($this->steps($manifest)),
            'Starting',
            '',
            ['from' => $this->currentVersion, 'to' => $manifest->version]
        );
    }

    /**
     * Version of the installed module.
     *
     * @return string
     */
    public function currentVersion(): string
    {
        return $this->currentVersion;
    }

    /**
     * Marker file: "this upgrade switched maintenance mode on".
     *
     * @return string
     */
    private function maintenanceMarker(): string
    {
        return $this->workDir . '/maintenance-by-upgrade';
    }

    /**
     * Where the previous version is kept during the upgrade.
     *
     * @return string
     */
    public function backupDir(): string
    {
        return $this->workDir . '/previous-' . PackageInspector::TOP_FOLDER;
    }

    /**
     * Builds the result array of check().
     *
     * @param ReleaseManifest|null $manifest
     * @param string|null $error
     * @param int $checkedAt
     * @return array
     */
    private function result(?ReleaseManifest $manifest, ?string $error, int $checkedAt): array
    {
        return [
            'manifest' => $manifest,
            'error' => $error,
            'checked_at' => $checkedAt,
            'newer' => $manifest !== null && $this->parser->isNewer($manifest->version, $this->currentVersion),
        ];
    }

    /**
     * Stores the verified manifest text so that the next check within 6 hours needs no network.
     *
     * @param string $file
     * @param int $now
     * @param ReleaseManifest $manifest
     * @return void
     */
    private function saveCache(string $file, int $now, ReleaseManifest $manifest): void
    {
        Files::write($file, (string) json_encode([
            'checked_at' => $now,
            'manifest_json' => $manifest->rawJson,
        ]));
    }
}
