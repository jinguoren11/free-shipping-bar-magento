<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Upgrade;

use GetDropFlow\FreeShippingBar\Model\Upgrade\ReleaseManifest;
use GetDropFlow\FreeShippingBar\Model\Upgrade\UpgradeRejected;
use PHPUnit\Framework\TestCase;

class ReleaseManifestTest extends TestCase
{
    /** @var string */
    private $secret;
    /** @var string */
    private $public;

    protected function setUp(): void
    {
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->public = base64_encode(sodium_crypto_sign_publickey($pair));
    }

    private function signed(array $override = [], ?string $secret = null): string
    {
        $data = $override + [
            'format' => 1,
            'version' => '1.0.1',
            'url' => 'https://github.com/jinguoren11/free-shipping-bar-magento/releases/download/v1.0.1/x-1.0.1-direct.zip',
            'sha256' => str_repeat('a', 64),
            'bytes' => 12345,
            'min_from' => '1.0.0',
            'notes' => ['en' => 'Fixes', 'zh' => '修复'],
        ];
        $data['signature'] = base64_encode(sodium_crypto_sign_detached(ReleaseManifest::canonical($data), $secret ?? $this->secret));
        return (string) json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function assertRejected(string $reason, callable $fn): void
    {
        try {
            $fn();
            $this->fail('expected rejection ' . $reason);
        } catch (UpgradeRejected $e) {
            $this->assertSame($reason, $e->reason);
        }
    }

    public function testGoodManifest(): void
    {
        $m = ReleaseManifest::parse($this->signed(), $this->public);
        $this->assertSame('1.0.1', $m->version);
        $this->assertSame('1.0.0', $m->minFrom);
        $this->assertSame('修复', $m->notes['zh']);
    }

    public function testNoPublicKeyMeansUpgradeIsOff(): void
    {
        $this->assertRejected('NO_PUBLIC_KEY', function () {
            ReleaseManifest::parse($this->signed(), '');
        });
    }

    public function testSignedWithAnotherKeyIsRefused(): void
    {
        $other = sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair());
        $this->assertRejected('BAD_SIGNATURE', function () use ($other) {
            ReleaseManifest::parse($this->signed([], $other), $this->public);
        });
    }

    public function testAnyChangedFieldBreaksTheSignature(): void
    {
        $data = json_decode($this->signed(), true);
        foreach (['version' => '9.9.9', 'sha256' => str_repeat('b', 64), 'bytes' => 1, 'url' => 'https://github.com/jinguoren11/free-shipping-bar-magento/releases/download/v1.0.1/evil.zip'] as $k => $v) {
            $copy = $data;
            $copy[$k] = $v;
            $this->assertRejected('BAD_SIGNATURE', function () use ($copy) {
                ReleaseManifest::parse((string) json_encode($copy), $this->public);
            });
        }
    }

    public function testDownloadMustComeFromTheAllowedAddress(): void
    {
        $this->assertRejected('BAD_URL', function () {
            ReleaseManifest::parse($this->signed(['url' => 'https://github.com/someone-else/other-repo/releases/download/v1/x.zip']), $this->public);
        });
        $this->assertRejected('BAD_URL', function () {
            ReleaseManifest::parse($this->signed(['url' => 'http://github.com/jinguoren11/free-shipping-bar-magento/releases/download/v1/x.zip']), $this->public);
        });
        $this->assertRejected('BAD_URL', function () {
            ReleaseManifest::parse($this->signed(['url' => 'https://github.com/jinguoren11/free-shipping-bar-magento/releases/download/../../x.zip']), $this->public);
        });
    }

    public function testTestPrefixAllowsLocalhostOnlyWhenGiven(): void
    {
        $json = $this->signed(['url' => 'http://127.0.0.1:8765/x.zip']);
        $m = ReleaseManifest::parse($json, $this->public, ['http://127.0.0.1:8765/']);
        $this->assertSame('http://127.0.0.1:8765/x.zip', $m->url);
    }

    public function testBadShapeIsRefused(): void
    {
        foreach ([['version' => '1.0'], ['sha256' => 'xyz'], ['bytes' => 0], ['bytes' => 99999999999], ['format' => 2], ['min_from' => 'abc']] as $bad) {
            $this->assertRejected($bad === ['format' => 2] ? 'BAD_MANIFEST' : 'BAD_MANIFEST', function () use ($bad) {
                ReleaseManifest::parse($this->signed($bad), $this->public);
            });
        }
        $this->assertRejected('BAD_MANIFEST', function () {
            ReleaseManifest::parse('not json', $this->public);
        });
    }

    public function testVersionComparison(): void
    {
        $this->assertTrue(ReleaseManifest::isNewer('1.0.1', '1.0.0'));
        $this->assertTrue(ReleaseManifest::isNewer('1.10.0', '1.9.9'));
        $this->assertFalse(ReleaseManifest::isNewer('1.0.0', '1.0.0'));
        $this->assertFalse(ReleaseManifest::isNewer('0.9.9', '1.0.0'));
        $this->assertFalse(ReleaseManifest::isNewer('1.0.1-evil', '1.0.0'));
    }

    public function testOtherRepositoriesAndLookalikeHostsAreRefused(): void
    {
        foreach ([
            'https://github.com.evil.example/jinguoren11/free-shipping-bar-magento/releases/download/v1/x.zip',
            'https://github.com/jinguoren11/free-shipping-bar-magento-evil/releases/download/v1/x.zip',
            'https://raw.githubusercontent.com/jinguoren11/free-shipping-bar-magento/main/x.zip',
        ] as $url) {
            $this->assertRejected('BAD_URL', function () use ($url) {
                ReleaseManifest::parse($this->signed(['url' => $url]), $this->public);
            });
        }
    }
}
