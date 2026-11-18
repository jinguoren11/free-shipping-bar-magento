<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\CommandsStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\DownloadStep;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\ExtractStep;
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
        $cached = is_file($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : null;
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
        $cached = is_file($cacheFile) ? json_decode((string) file_get_contents($cacheFile), true) : null;
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
     *     can_run_commands, free_bytes
     * @return string[]
     */
    public function preflight(ReleaseManifest $manifest, array $serverFacts): array
    {
        $facts = array_merge($serverFacts, [
            'current_version' => $this->currentVersion,
            'key_present' => $this->publicKey !== '',
            'has_zip' => class_exists(\ZipArchive::class),
            'upgrade_running' => $this->status->isRunning(),
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
            Step\Files::removeTree($this->backupDir());
            Step\Files::removeTree($this->workDir . '/new');
            if (is_file($this->workDir . '/package.zip')) {
                unlink($this->workDir . '/package.zip');
            }
        }
        return $result;
    }

    /**
     * After a killed upgrade (no sign of life for a quarter of an hour): put the old version back.
     * Returns true when something had to be restored.
     *
     * @return bool
     */
    public function recoverIfDead(): bool
    {
        $data = $this->status->read();
        if ($data['state'] !== UpgradeStatus::RUNNING || empty($data['stale'])) {
            return false;
        }
        $swap = new SwapStep($this->moduleDir, $this->workDir . '/new', $this->backupDir());
        try {
            if ($swap->recover()) {
                foreach ($this->rebuildCommands() as $arguments) {
                    $this->commands->magento($arguments);
                }
            }
            MaintenanceStep::leave($this->commands, $this->maintenanceMarker());
        } catch (\Throwable $e) {
            $this->status->write(
                UpgradeStatus::NEEDS_MANUAL,
                0,
                0,
                '',
                'Restore did not finish.',
                ['reason' => 'RESTORE_FAILED']
            );
            return true;
        }
        $this->status->write(
            UpgradeStatus::ROLLED_BACK,
            0,
            0,
            '',
            'The upgrade was interrupted; your previous version was put back.',
            ['reason' => 'INTERRUPTED']
        );
        return true;
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
     * Writes "running, step 0" at once so that the card shows progress before the worker has started.
     *
     * @param ReleaseManifest $manifest
     * @return void
     */
    public function markQueued(ReleaseManifest $manifest): void
    {
        $this->status->write(
            UpgradeStatus::RUNNING,
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
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0775, true);
        }
        file_put_contents($file, (string) json_encode([
            'checked_at' => $now,
            'manifest_json' => $manifest->rawJson,
        ]), LOCK_EX);
    }
}
