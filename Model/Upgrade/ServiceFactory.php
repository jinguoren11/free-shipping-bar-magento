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

    /**
     * @param DirectoryList $directories
     * @param ComponentRegistrarInterface $registrar
     * @param State $state
     * @param StoreManagerInterface $stores
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     */
    public function __construct(
        DirectoryList $directories,
        ComponentRegistrarInterface $registrar,
        State $state,
        StoreManagerInterface $stores,
        \Magento\Framework\App\Config\ScopeConfigInterface $config
    ) {
        $this->directories = $directories;
        $this->registrar = $registrar;
        $this->state = $state;
        $this->stores = $stores;
        $this->config = $config;
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
        $composer = is_file($moduleDir . '/composer.json')
            ? json_decode((string) file_get_contents($moduleDir . '/composer.json'), true) : [];

        return new UpgradeService(
            $workDir,
            $moduleDir,
            new UpgradeStatus($workDir . '/status.json'),
            new ProcessCommandRunner($this->phpBinary(), $root),
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
        $canRun = function_exists('proc_open') && function_exists('exec')
            && !in_array('proc_open', $disabled, true) && !in_array('exec', $disabled, true)
            && is_file($root . '/bin/magento') && is_executable($this->phpBinary());

        return [
            'in_app_code' => $real !== '' && strpos($real, (string) realpath($root . '/app/code') . '/') === 0
                && !is_link($moduleDir),
            'module_writable' => is_writable($moduleDir),
            'parent_writable' => is_writable(dirname($moduleDir)),
            'can_run_commands' => $canRun,
            'free_bytes' => (int) @disk_free_space($this->directories->getPath(DirectoryList::VAR_DIR)),
        ];
    }

    /**
     * Command line that starts the upgrade in the background.
     *
     * @return string
     */
    public function backgroundCommand(): string
    {
        return escapeshellarg($this->phpBinary()) . ' -d memory_limit=-1 '
            . escapeshellarg($this->directories->getRoot() . '/bin/magento')
            . ' getdropflow:fsb:upgrade > /dev/null 2>&1 &';
    }

    /**
     * Path of the php program (the web server's own binary may be php-fpm, so use the one next to it).
     *
     * @return string
     */
    private function phpBinary(): string
    {
        $candidate = PHP_BINDIR . '/php';
        return is_executable($candidate) ? $candidate : 'php';
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
