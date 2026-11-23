<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Authorization\AnswerParser;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Authorization\LinkService;
use GetDropFlow\FreeShippingBar\Model\Authorization\RecordKeeper;
use GetDropFlow\FreeShippingBar\Model\Authorization\StatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support.php';

class LinkServiceTest extends TestCase
{
    private const NOW = 1800000000;
    private const DEVICE = 'dev_abcdefghijklmnopqrstuvwxyz0123456789';
    private const TOKEN = 'gdt_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP';

    /** @var Signer */
    private $signer;
    /** @var MemoryStore */
    private $store;
    /** @var FakeTransport */
    private $transport;
    /** @var FixedClock */
    private $clock;
    /** @var LinkService */
    private $link;

    protected function setUp(): void
    {
        $this->signer = new Signer();
        $this->store = new MemoryStore();
        $this->transport = new FakeTransport();
        $this->clock = new FixedClock(self::NOW);
        $status = new StatusService(
            $this->store,
            $this->transport,
            new FixedDomain(),
            $this->clock,
            new AnswerParser(),
            new RecordKeeper(),
            'https://license.example.test',
            $this->signer->public
        );
        $this->link = new LinkService($this->store, $this->transport, new FixedDomain(), $this->clock, $status, 'https://license.example.test');
    }

    private function json(int $status, array $data): array
    {
        return ['status' => $status, 'body' => json_encode($data)];
    }

    private function started(): void
    {
        $this->transport->queue[] = $this->json(200, [
            'device_code' => self::DEVICE, 'user_code' => 'BCDF-GHJK', 'verification_url' => 'https://getdropflow.com/en/link/?code=BCDF-GHJK',
            'expires_in' => 600, 'interval' => 3,
        ]);
        $this->link->begin('en');
    }

    public function testBeginAsksForTheShippingBarProductAndSendsOnlyTheDomain(): void
    {
        $this->transport->queue[] = $this->json(200, [
            'device_code' => self::DEVICE, 'user_code' => 'BCDF-GHJK', 'verification_url' => 'https://getdropflow.com/en/link/?code=BCDF-GHJK',
            'expires_in' => 600, 'interval' => 3,
        ]);
        $r = $this->link->begin('es');
        $this->assertSame('waiting', $r['result']);
        $this->assertSame('BCDF-GHJK', $r['user_code']);
        $req = $this->transport->requests[0];
        $this->assertSame('POST', $req['method']);
        $this->assertSame('https://license.example.test/v1/link/start', $req['url']);
        $this->assertSame(['domain' => 'shop.example.com', 'product' => 'shipping-bar', 'lang' => 'es'], json_decode((string) $req['body'], true));
        $this->assertSame(self::DEVICE, $this->store->pending['device_code']);
        $this->assertSame(self::NOW + 600, $this->store->pending['expires']);
        $this->assertSame('', $this->store->credential, 'nothing is authorized yet');
    }

    public function testBeginRefusesBadAnswers(): void
    {
        $good = ['device_code' => self::DEVICE, 'user_code' => 'BCDF-GHJK', 'verification_url' => 'https://getdropflow.com/x', 'expires_in' => 600, 'interval' => 3];
        foreach ([
            ['verification_url' => 'http://getdropflow.com/x'],
            ['verification_url' => "https://a.com/x y"],
            ['user_code' => 'abcd'],
            ['device_code' => 'short'],
        ] as $bad) {
            $this->transport->queue[] = $this->json(200, $bad + $good);
            $this->assertSame('failed', $this->link->begin('en')['result']);
        }
        $this->transport->queue[] = $this->json(500, ['error' => 'x']);
        $this->assertSame('failed', $this->link->begin('en')['result']);
        $this->transport->queue[] = $this->json(429, ['error' => 'rate']);
        $this->assertSame('rate_limited', $this->link->begin('en')['result']);
        $this->transport->queue[] = new \RuntimeException('down');
        $this->assertSame('unreachable', $this->link->begin('en')['result']);
        $this->assertNull($this->store->pending);
    }

    public function testBeginClampsTheTimesTheServerSends(): void
    {
        $this->transport->queue[] = $this->json(200, [
            'device_code' => self::DEVICE, 'user_code' => 'BCDF-GHJK', 'verification_url' => 'https://getdropflow.com/x', 'expires_in' => 999999, 'interval' => 0,
        ]);
        $r = $this->link->begin('en');
        $this->assertSame(1800, $r['expires_in']);
        $this->assertSame(2, $r['interval']);
    }

    public function testAnApprovalSavesTheTokenVerifiesItAndAuthorizes(): void
    {
        $this->started();
        $this->transport->queue[] = $this->json(200, ['status' => 'pending']);
        $this->assertSame('waiting', $this->link->poll()['result']);
        $this->assertNotNull($this->store->pending);

        $this->transport->queue[] = $this->json(200, ['status' => 'approved', 'license_key' => self::TOKEN, 'credential' => 'site_token', 'plan' => 'free']);
        $this->transport->queue[] = $this->signer->answer([]);
        $this->assertSame('linked', $this->link->poll()['result']);
        $this->assertSame(self::TOKEN, $this->store->credential);
        $this->assertNull($this->store->pending);
        $this->assertTrue((new AuthorizationGate($this->store, $this->clock))->isAllowed());
        $this->assertSame(self::TOKEN, $this->transport->requests[3]['headers']['X-License-Key']);
    }

    public function testATokenTheServerDoesNotVouchForDoesNotAuthorize(): void
    {
        $this->started();
        $this->transport->queue[] = $this->json(200, ['status' => 'approved', 'license_key' => self::TOKEN]);
        $this->transport->queue[] = (new Signer())->answer([]); // a different key signs: not the real server
        $this->assertSame('linked_unverified', $this->link->poll()['result']);
        $this->assertFalse((new AuthorizationGate($this->store, $this->clock))->isAllowed());
    }

    public function testDeniedExpiredAndGoneEndTheAttempt(): void
    {
        $this->started();
        $this->transport->queue[] = $this->json(200, ['status' => 'denied']);
        $this->assertSame('denied', $this->link->poll()['result']);
        $this->assertNull($this->store->pending);
        $this->started();
        $this->transport->queue[] = $this->json(410, ['error' => 'expired']);
        $this->assertSame('expired', $this->link->poll()['result']);
        $this->assertNull($this->store->pending);
        $this->started();
        $this->clock->now = self::NOW + 601;
        $this->assertSame('expired', $this->link->poll()['result']);
        $this->assertNull($this->store->pending);
        $this->assertSame('none', $this->link->poll()['result']);
    }

    public function testAnUnreachableWebsiteKeepsTheAttemptAndSlowDownToo(): void
    {
        $this->started();
        $this->transport->queue[] = new \RuntimeException('down');
        $this->assertSame('unreachable', $this->link->poll()['result']);
        $this->transport->queue[] = $this->json(429, ['error' => 'slow_down']);
        $this->assertSame('unreachable', $this->link->poll()['result']);
        $this->assertNotNull($this->store->pending);
    }

    public function testABadTokenShapeIsNotSaved(): void
    {
        $this->started();
        $this->transport->queue[] = $this->json(200, ['status' => 'approved', 'license_key' => 'bad key!']);
        $this->assertSame('failed', $this->link->poll()['result']);
        $this->assertSame('', $this->store->credential);
        $this->assertNull($this->store->pending);
    }

    public function testPendingForThePageForgetsAnAttemptWhoseTimeIsUp(): void
    {
        $this->started();
        $this->assertSame('BCDF-GHJK', $this->link->pending()['user_code']);
        $this->clock->now = self::NOW + 700;
        $this->assertNull($this->link->pending());
        $this->assertNull($this->store->pending);
    }

    public function testCancel(): void
    {
        $this->started();
        $this->link->cancel();
        $this->assertNull($this->store->pending);
    }

    public function testATypedKeyIsCheckedImmediately(): void
    {
        $this->assertSame('failed', $this->link->saveTypedKey('no good'));
        $this->assertSame('', $this->store->credential);
        $this->transport->queue[] = $this->signer->answer([]);
        $this->assertSame('linked', $this->link->saveTypedKey('  KEY-1234567890-abcdef  '));
        $this->assertSame('KEY-1234567890-abcdef', $this->store->credential);
    }

    public function testNothingIsSentWhenTheBaseAddressIsNotHttps(): void
    {
        $status = $this->createMock(StatusService::class);
        $link = new LinkService($this->store, $this->transport, new FixedDomain(), $this->clock, $status, 'http://license.example.test');
        $this->assertSame('not_configured', $link->begin('en')['result']);
        $this->assertSame([], $this->transport->requests);
    }
}
