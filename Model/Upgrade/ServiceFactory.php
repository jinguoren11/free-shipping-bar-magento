<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Shell;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\Files;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Builds the UpgradeService from the real Magento environment (download edition only).
 */
class ServiceFactory
{
    private const MODULE = 'GetDropFlow_FreeShippingBar';

    /** @var DirectoryList */
    private $directories;
    /** @var ComponentRegistrarInterface */
    private $registrar;
    /** @var State */
    private $state;
    /** @var StoreManagerInterface */
    private $stores;
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    private $config;
    /** @var Shell */
    private $shell;
    /** @var string|null */
    private $php;

    /**
     * @param DirectoryList $directories
     * @param ComponentRegistrarInterface $registrar
     * @param State $state
     * @param StoreManagerInterface $stores
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param Shell $shell
     */
    public function __construct(
        DirectoryList $directories,
        ComponentRegistrarInterface $registrar,
        State $state,
        StoreManagerInterface $stores,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        Shell $shell
    ) {
        $this->directories = $directories;
        $this->registrar = $registrar;
        $this->state = $state;
        $this->stores = $stores;
        $this->config = $config;
        $this->shell = $shell;
    }

    /**
     * Creates the service.
     *
     * @return UpgradeService
     */
    public function create(): UpgradeService
    {
        $root = $this->directories->getRoot();
        $moduleDir = (string) $this->registrar->getPath(ComponentRegistrar::MODULE, self::MODULE);
        $workDir = $this->directories->getPath(DirectoryList::VAR_DIR) . '/getdropflow_fsb_upgrade';
        $parser = new ReleaseManifestParser();
        // Test hook for the test team only: a release server on this very machine (never an outside address).
        $test = (string) getenv('GETDROPFLOW_FSB_TEST_RELEASES');
        $local = preg_match('#^http://127\.0\.0\.1:\d{2,5}/$#', $test) === 1;
        $http = new CurlHttpClient($local ? $test : '');
        $composer = json_decode(Files::read($moduleDir . '/composer.json'), true);

        return new UpgradeService(
            $workDir,
            $moduleDir,
            new UpgradeStatus($workDir . '/status.json'),
            new ShellCommandRunner($this->shell, $this->phpBinary(), $root),
            $http,
            new PackageInspector(),
            $parser,
            new ReleaseSource($http, $parser),
            new Preflight($parser),
            ReleaseKey::PUBLIC_KEY_BASE64,
            is_array($composer) ? (string) ($composer['version'] ?? '') : '',
            $this->state->getMode() === State::MODE_PRODUCTION,
            $this->locales(),
            $local ? $test . 'latest.json' : ReleaseManifest::LATEST_RELEASE_API,
            $local ? [$test] : [ReleaseManifest::DEFAULT_URL_PREFIX]
        );
    }

    /**
     * Facts about this server that the pre-flight needs.
     *
     * @return array
     */
    public function serverFacts(): array
    {
        $root = $this->directories->getRoot();
        $moduleDir = (string) $this->registrar->getPath(ComponentRegistrar::MODULE, self::MODULE);
        $real = (string) realpath($moduleDir);
        $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        $canRun = function_exists('proc_open') && !in_array('proc_open', $disabled, true)
            && is_file($root . '/bin/magento');

        return [
            'in_app_code' => $real !== '' && strpos($real, (string) realpath($root . '/app/code') . '/') === 0
                && !is_link($moduleDir),
            'module_writable' => is_writable($moduleDir),
            'parent_writable' => is_writable(dirname($moduleDir)),
            'can_run_commands' => $canRun,
            'php_found' => $this->phpBinary() !== '',
            'free_bytes' => Files::freeBytes($this->directories->getPath(DirectoryList::VAR_DIR)),
        ];
    }

    /**
     * Starts the background worker (bin/magento getdropflow:fsb:upgrade) and returns at once. The worker must use the
     * same PHP version as this web server (see phpBinary()).
     *
     * @return void
     * @throws UpgradeRejected when the process cannot be started
     */
    public function startInBackground(): void
    {
        $php = $this->phpBinary();
        if ($php === '') {
            throw new UpgradeRejected('PHP_NOT_FOUND', 'No PHP program matching this server was found.');
        }
        try {
            $this->shell->execute(
                '(%s -d memory_limit=-1 %s getdropflow:fsb:upgrade > /dev/null 2>&1 &)',
                [$php, $this->directories->getRoot() . '/bin/magento']
            );
        } catch (\Throwable $e) {
            throw new UpgradeRejected('COMMAND_FAILED', 'The background process could not be started.');
        }
    }

    /**
     * The php program for the background commands: it must have the PHP version of the web server, because the
     * code (and vendor folder) of the shop is only guaranteed to work on that one. A server often has several
     * (php8.3 next to a default php 8.5), and the web server's own binary is php-fpm, not php.
     *
     * Order: the command line process itself (it already is the right one), then phpMAJOR.MINOR / phpMAJORMINOR
     * next to PHP, in /usr/bin and /usr/local/bin, then a plain "php" whose version is checked.
     *
     * @return string path, or '' when none matches
     */
    public function phpBinary(): string
    {
        if ($this->php !== null) {
            return $this->php;
        }
        if (PHP_SAPI === 'cli' && PHP_BINARY !== '') {
            return $this->php = PHP_BINARY;
        }
        $dirs = array_values(array_unique(array_filter(
            [PHP_BINDIR, dirname(PHP_BINARY), '/usr/bin', '/usr/local/bin']
        )));

        return $this->php = (new PhpLocator())->locate(
            $dirs,
            (string) PHP_MAJOR_VERSION,
            (string) PHP_MINOR_VERSION,
            'is_executable',
            function (string $binary): string {
                return $this->versionOf($binary);
            }
        );
    }

    /**
     * Asks a php program for its major.minor version.
     *
     * @param string $binary
     * @return string
     */
    private function versionOf(string $binary): string
    {
        try {
            return trim((string) $this->shell->execute(
                '%s -r %s',
                [$binary, 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;']
            ));
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Locales to deploy static files for: all store views plus en_US.
     *
     * @return string[]
     */
    private function locales(): array
    {
        $locales = ['en_US'];
        foreach ($this->stores->getStores() as $store) {
            $code = (string) $this->config->getValue('general/locale/code', 'store', $store->getId());
            if (preg_match('/^[a-z]{2}_[A-Z]{2}$/', $code) === 1) {
                $locales[$code] = $code;
            }
        }
        return array_values(array_unique($locales));
    }
}
