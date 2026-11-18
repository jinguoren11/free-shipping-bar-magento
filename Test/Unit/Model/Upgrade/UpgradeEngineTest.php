<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\CommandRunner;
use GetDropFlow\FreeShippingBar\Model\Upgrade\HttpClient;
use GetDropFlow\FreeShippingBar\Model\Upgrade\PackageInspector;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Preflight;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifest;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifestParser;
use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseSource;
use GetDropFlow\FreeShippingBar\Model\Upgrade\Step\Files;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRunner;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeService;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStatus;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeStep;
use PHPUnit\Framework\TestCase;

/**
 * The upgrade engine without Magento: fake web, fake bin/magento, real files in a temp folder.
 */
class UpgradeEngineTest extends TestCase
{
    private const PREFIX = ReleaseManifest::DEFAULT_URL_PREFIX;

    /** @var string */
    private $dir;
    /** @var string */
    private $secret;
    /** @var string */
    private $public;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/fsb-engine-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/app/code/GetDropFlow/FreeShippingBar/etc', 0775, true);
        file_put_contents($this->dir . '/app/code/GetDropFlow/FreeShippingBar/etc/module.xml', '<config/>');
        file_put_contents($this->dir . '/app/code/GetDropFlow/FreeShippingBar/composer.json', '{"version":"1.0.0"}');
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->public = base64_encode(sodium_crypto_sign_publickey($pair));
    }

    protected function tearDown(): void
    {
        Files::removeTree($this->dir);
    }

    private function zipFor(string $version, string $name = 'v.zip'): string
    {
        $path = $this->dir . '/' . $name;
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE);
        $zip->addFromString('FreeShippingBar/etc/module.xml', '<config/>');
        $zip->addFromString('FreeShippingBar/composer.json', '{"version":"' . $version . '"}');
        $zip->close();
        return $path;
    }

    private function signedManifest(string $zip, string $version, ?string $minFrom = null): string
    {
        $data = [
            'format' => 1, 'version' => $version, 'url' => self::PREFIX . 'v' . $version . '/x.zip',
            'sha256' => hash_file('sha256', $zip), 'bytes' => (int) filesize($zip), 'notes' => ['en' => 'n'],
        ];
        if ($minFrom !== null) {
            $data['min_from'] = $minFrom;
        }
        $data['signature'] = base64_encode(sodium_crypto_sign_detached(
            (new ReleaseManifestParser())->canonical($data),
            $this->secret
        ));
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES);
    }

    private function fakeHttp(string $zip, string $manifestJson, int &$calls): HttpClient
    {
        return new class ($zip, $manifestJson, $calls) implements HttpClient {
            private $zip;
            private $manifest;
            private $calls;

            public function __construct(string $zip, string $manifest, &$calls)
            {
                $this->zip = $zip;
                $this->manifest = $manifest;
                $this->calls = &$calls;
            }

            public function getText(string $url, int $maxBytes): string
            {
                $this->calls++;
                if (strpos($url, 'api.github.com') !== false) {
                    return (string) json_encode(['assets' => [
                        ['name' => 'x.zip', 'browser_download_url' => 'https://example.invalid/x.zip'],
                        ['name' => 'manifest.json', 'browser_download_url' => 'https://github.com/m.json'],
                    ]]);
                }
                return $this->manifest;
            }

            public function download(string $url, string $destination, int $maxBytes): void
            {
                $this->calls++;
                copy($this->zip, $destination);
            }
        };
    }

    private function fakeCommands(?string $failOnce = null): object
    {
        return new class ($failOnce) implements CommandRunner {
            public $log = [];
            public $maintenanceOn = false;
            private $failOnce;

            public function __construct(?string $failOnce)
            {
                $this->failOnce = $failOnce;
            }

            public function magento(array $arguments): string
            {
                $this->log[] = implode(' ', $arguments);
                if ($this->failOnce !== null && $arguments[0] === $this->failOnce) {
                    $this->failOnce = null;
                    throw new UpgradeRejected('COMMAND_FAILED', 'boom');
                }
                if ($arguments[0] === 'maintenance:status') {
                    return $this->maintenanceOn ? 'Status: maintenance mode is active.' : 'Status: maintenance mode is not active.';
                }
                return $arguments[0] === 'module:status' ? 'Module is enabled' : '';
            }
        };
    }

    private function service(HttpClient $http, CommandRunner $commands, string $version = '1.0.0'): UpgradeService
    {
        $parser = new ReleaseManifestParser();
        return new UpgradeService(
            $this->dir . '/var/work',
            $this->dir . '/app/code/GetDropFlow/FreeShippingBar',
            new UpgradeStatus($this->dir . '/var/work/status.json'),
            $commands,
            $http,
            new PackageInspector(),
            $parser,
            new ReleaseSource($http, $parser),
            new Preflight($parser),
            $this->public,
            $version,
            true,
            ['en_US', 'es_ES']
        );
    }

    private function installedVersion(): string
    {
        $c = json_decode((string) file_get_contents(
            $this->dir . '/app/code/GetDropFlow/FreeShippingBar/composer.json'
        ), true);
        return $c['version'];
    }

    public function testFullUpgradeSucceeds(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $commands = $this->fakeCommands();
        $service = $this->service($this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls), $commands);
        $check = $service->check(true);
        $this->assertTrue($check['newer']);
        $this->assertSame('done', $service->execute($check['manifest']));
        $this->assertSame('1.0.1', $this->installedVersion());
        $this->assertSame(['maintenance:status', 'maintenance:enable'], array_slice($commands->log, 0, 2));
        $this->assertContains('setup:di:compile', $commands->log);
        $this->assertContains('setup:static-content:deploy -f en_US es_ES', $commands->log);
        $this->assertSame('maintenance:disable', end($commands->log));
        $this->assertFileDoesNotExist($this->dir . '/var/work/maintenance-by-upgrade');
        $this->assertDirectoryDoesNotExist($service->backupDir());
        $this->assertSame('done', (new UpgradeStatus($this->dir . '/var/work/status.json'))->read()['state']);
    }

    public function testFailureAfterTheSwapPutsTheOldVersionBack(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $commands = $this->fakeCommands('setup:upgrade');
        $service = $this->service($this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls), $commands);
        $manifest = $service->check(true)['manifest'];
        $this->assertSame('rolled_back', $service->execute($manifest));
        $this->assertSame('1.0.0', $this->installedVersion(), 'old code must be back');
        $this->assertSame('maintenance:disable', end($commands->log));
        $this->assertSame(2, count(array_keys($commands->log, 'setup:upgrade --keep-generated')), 'rebuilt on old code');
        $state = (new UpgradeStatus($this->dir . '/var/work/status.json'))->read();
        $this->assertSame('rolled_back', $state['state']);
        $this->assertSame('COMMAND_FAILED', $state['reason']);
    }

    public function testMaintenanceModeSwitchedOnByTheOwnerIsLeftOn(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $commands = $this->fakeCommands();
        $commands->maintenanceOn = true;
        $service = $this->service($this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls), $commands);
        $this->assertSame('done', $service->execute($service->check(true)['manifest']));
        $this->assertNotContains('maintenance:enable', $commands->log);
        $this->assertNotContains('maintenance:disable', $commands->log);
    }

    public function testFailedVerifyAlsoRollsBack(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $commands = $this->fakeCommands('module:status');
        $service = $this->service($this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls), $commands);
        $this->assertSame('rolled_back', $service->execute($service->check(true)['manifest']));
        $this->assertSame('1.0.0', $this->installedVersion());
    }

    public function testTamperedDownloadChangesNothing(): void
    {
        $good = $this->zipFor('1.0.1');
        $manifest = $this->signedManifest($good, '1.0.1');
        $evil = $this->zipFor('1.0.1', 'evil.zip');
        $z = new \ZipArchive();
        $z->open($evil);
        $z->addFromString('FreeShippingBar/extra.php', '<?php evil();');
        $z->close();
        $calls = 0;
        $commands = $this->fakeCommands();
        $service = $this->service($this->fakeHttp($evil, $manifest, $calls), $commands);
        $this->assertSame('rolled_back', $service->execute($service->check(true)['manifest']));
        $this->assertSame('1.0.0', $this->installedVersion());
        $this->assertNotContains('maintenance:enable', $commands->log, 'nothing is touched before the download is OK');
    }

    public function testCheckUsesTheCacheAndTheTenMinutePause(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $service = $this->service(
            $this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls),
            $this->fakeCommands()
        );
        $service->check(false, 1000);
        $this->assertSame(2, $calls, 'API + manifest');
        $service->check(false, 1000 + 3600);
        $service->check(true, 1000 + 599);
        $this->assertSame(2, $calls, 'cache, and a forced check inside 10 minutes does not hit the network');
        $service->check(true, 1000 + 601);
        $this->assertSame(4, $calls);
        $service->check(false, 1000 + 601 + UpgradeService::CACHE_SECONDS + 1);
        $this->assertSame(6, $calls);
    }

    public function testCheckWithoutPublicKeyStaysOff(): void
    {
        $calls = 0;
        $parser = new ReleaseManifestParser();
        $http = $this->fakeHttp($this->zipFor('1.0.1'), '{}', $calls);
        $service = new UpgradeService(
            $this->dir . '/var/work',
            $this->dir . '/m',
            new UpgradeStatus($this->dir . '/s.json'),
            $this->fakeCommands(),
            $http,
            new PackageInspector(),
            $parser,
            new ReleaseSource($http, $parser),
            new Preflight($parser),
            '',
            '1.0.0',
            false,
            []
        );
        $result = $service->check(true);
        $this->assertSame('NO_PUBLIC_KEY', $result['error']);
        $this->assertSame(0, $calls, 'no network access without a key');
    }

    public function testForeignSignatureIsRefused(): void
    {
        $zip = $this->zipFor('1.0.1');
        $other = base64_encode(sodium_crypto_sign_publickey(sodium_crypto_sign_keypair()));
        $calls = 0;
        $http = $this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls);
        $this->expectException(UpgradeRejected::class);
        (new ReleaseSource($http, new ReleaseManifestParser()))->latest($other);
    }

    public function testRecoverAfterAKilledUpgrade(): void
    {
        $zip = $this->zipFor('1.0.1');
        $calls = 0;
        $commands = $this->fakeCommands();
        $service = $this->service($this->fakeHttp($zip, $this->signedManifest($zip, '1.0.1'), $calls), $commands);
        $steps = $service->steps($service->check(true)['manifest']);
        foreach (array_slice($steps, 0, 4) as $step) { // killed right after the swap
            $step->run();
        }
        $this->assertSame('1.0.1', $this->installedVersion());
        $status = new UpgradeStatus($this->dir . '/var/work/status.json');
        $status->write(UpgradeStatus::RUNNING, 4, 6);
        $this->assertFalse($service->recoverIfDead(), 'a live upgrade is left alone');
        $raw = json_decode((string) file_get_contents($this->dir . '/var/work/status.json'), true);
        $raw['time'] = time() - UpgradeStatus::STALE_SECONDS - 5;
        file_put_contents($this->dir . '/var/work/status.json', json_encode($raw));
        $this->assertTrue($service->recoverIfDead());
        $this->assertSame('1.0.0', $this->installedVersion());
        $this->assertSame('rolled_back', $status->read()['state']);
    }

    public function testRunnerUndoesStartedStepsInReverseAndReportsNeedsManual(): void
    {
        $log = [];
        $make = function (string $name, bool $failRun, bool $failUndo) use (&$log): UpgradeStep {
            return new class ($name, $failRun, $failUndo, $log) implements UpgradeStep {
                private $name;
                private $failRun;
                private $failUndo;
                private $log;

                public function __construct($name, $failRun, $failUndo, &$log)
                {
                    $this->name = $name;
                    $this->failRun = $failRun;
                    $this->failUndo = $failUndo;
                    $this->log = &$log;
                }

                public function label(): string
                {
                    return $this->name;
                }

                public function run(): void
                {
                    $this->log[] = 'run ' . $this->name;
                    if ($this->failRun) {
                        throw new \RuntimeException('x');
                    }
                }

                public function undo(): void
                {
                    $this->log[] = 'undo ' . $this->name;
                    if ($this->failUndo) {
                        throw new \RuntimeException('y');
                    }
                }
            };
        };
        $status = new UpgradeStatus($this->dir . '/st.json');
        $runner = new UpgradeRunner($status);
        $finished = 0;
        $result = $runner->run(
            [$make('a', false, false), $make('b', true, false), $make('c', false, false)],
            function () use (&$log): void {
                $log[] = 'rebuild';
            },
            function () use (&$finished): void {
                $finished++;
            }
        );
        $this->assertSame('rolled_back', $result);
        $this->assertSame(['run a', 'run b', 'undo b', 'undo a', 'rebuild'], $log);
        $this->assertSame(1, $finished);

        $finished = 0;
        $result = $runner->run(
            [$make('a', false, true), $make('b', true, false)],
            function (): void {
            },
            function () use (&$finished): void {
                $finished++;
            }
        );
        $this->assertSame('needs_manual', $result);
        $this->assertSame(0, $finished, 'the shop stays in maintenance mode when the restore failed');
    }

    public function testPreflightGivesPlainReasons(): void
    {
        $zip = $this->zipFor('1.0.1');
        $parser = new ReleaseManifestParser();
        $manifest = $parser->parse($this->signedManifest($zip, '1.0.1', '1.0.0'), $this->public);
        $pre = new Preflight($parser);
        $ok = [
            'key_present' => true, 'in_app_code' => true, 'module_writable' => true, 'parent_writable' => true,
            'has_zip' => true, 'can_run_commands' => true, 'free_bytes' => Preflight::MIN_FREE_BYTES,
            'upgrade_running' => false, 'current_version' => '1.0.0',
        ];
        $this->assertSame([], $pre->check($ok, $manifest));
        $this->assertSame(['NOT_IN_APP_CODE'], $pre->check(['in_app_code' => false] + $ok, $manifest));
        $this->assertSame(['NOT_WRITABLE'], $pre->check(['module_writable' => false] + $ok, $manifest));
        $this->assertSame(['LOW_DISK'], $pre->check(['free_bytes' => 5] + $ok, $manifest));
        $this->assertSame(['ALREADY_RUNNING'], $pre->check(['upgrade_running' => true] + $ok, $manifest));
        $this->assertSame(['NOT_NEWER'], $pre->check(['current_version' => '1.0.1'] + $ok, $manifest));
        $this->assertContains('TOO_OLD', $pre->check(['current_version' => '0.9.0'] + $ok, $manifest));
        $this->assertSame(['CANNOT_RUN_COMMANDS'], $pre->check(['can_run_commands' => false] + $ok, $manifest));
        $this->assertSame(['NO_PUBLIC_KEY'], $pre->check(['key_present' => false] + $ok, $manifest));
    }

    public function testStatusFileStaleAndDamaged(): void
    {
        $file = $this->dir . '/status.json';
        $status = new UpgradeStatus($file);
        $this->assertSame('idle', $status->read()['state']);
        file_put_contents($file, '{broken');
        $this->assertSame('idle', $status->read()['state']);
        $status->write(UpgradeStatus::RUNNING, 1, 6, 'x');
        $this->assertTrue($status->isRunning());
        $this->assertFalse($status->isRunning(time() + UpgradeStatus::STALE_SECONDS + 5));
    }
}
