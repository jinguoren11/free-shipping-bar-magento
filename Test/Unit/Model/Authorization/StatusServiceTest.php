<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Authorization\AnswerParser;
use GetDropFlow\FreeShippingBar\Model\Authorization\AuthorizationGate;
use GetDropFlow\FreeShippingBar\Model\Authorization\RecordKeeper;
use GetDropFlow\FreeShippingBar\Model\Authorization\StatusService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support.php';

class StatusServiceTest extends TestCase
{
    private const NOW = 1800000000;
    private const TOKEN = 'gdt_abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOP';

    /** @var Signer */
    private $signer;
    /** @var MemoryStore */
    private $store;
    /** @var FakeTransport */
    private $transport;
    /** @var FixedClock */
    private $clock;

    protected function setUp(): void
    {
        $this->signer = new Signer();
        $this->store = new MemoryStore();
        $this->store->credential = self::TOKEN;
        $this->transport = new FakeTransport();
        $this->clock = new FixedClock(self::NOW);
    }

    private function service(): StatusService
    {
        return new StatusService(
            $this->store,
            $this->transport,
            new FixedDomain(),
            $this->clock,
            new AnswerParser(),
            new RecordKeeper(),
            'https://license.example.test',
            $this->signer->public
        );
    }

    private function allowed(): bool
    {
        return (new AuthorizationGate($this->store, $this->clock))->isAllowed();
    }

    public function testAFreeMembershipAuthorizes(): void
    {
        $this->transport->queue[] = $this->signer->answer([]);
        $this->assertSame(StatusService::VERIFIED, $this->service()->refresh());
        $this->assertSame('valid', $this->store->record['state']);
        $this->assertSame(self::NOW, $this->store->record['last_valid_at']);
        $this->assertTrue($this->allowed());
        $req = $this->transport->requests[0];
        $this->assertSame('GET', $req['method']);
        $this->assertSame('https://license.example.test/v1/license/status?domain=shop.example.com', $req['url']);
        $this->assertSame(['X-License-Key' => self::TOKEN], $req['headers']);
    }

    public function testNothingIsAskedWithoutACredential(): void
    {
        $this->store->credential = '';
        $this->assertSame(StatusService::NO_CREDENTIAL, $this->service()->refresh());
        $this->assertSame([], $this->transport->requests);
    }

    public function testAnAnswerSignedByAnotherKeyIsNotBelieved(): void
    {
        $other = new Signer();
        $this->transport->queue[] = $other->answer([]);
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->assertSame([], $this->store->record);
        $this->assertFalse($this->allowed());
    }

    public function testAnUnsignedAnswerIsNotBelieved(): void
    {
        $this->transport->queue[] = $this->signer->answer([], null, false);
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->assertSame([], $this->store->record);
    }

    public function testAnAnswerForAnotherDomainIsNotBelieved(): void
    {
        $this->transport->queue[] = $this->signer->answer(['domain' => 'evil.example.com']);
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->assertSame([], $this->store->record);
    }

    public function testAReplayedOldAnswerIsNotBelieved(): void
    {
        $this->transport->queue[] = $this->signer->answer([], self::NOW - 3 * 86400);
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
    }

    public function testNoConnectionChangesNothing(): void
    {
        $this->transport->queue[] = $this->signer->answer([]);
        $this->service()->refresh();
        $before = $this->store->record;
        $this->clock->now = self::NOW + 5 * 86400;
        $this->transport->queue[] = new \RuntimeException('timeout');
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->assertSame($before, $this->store->record);
        $this->assertTrue($this->allowed(), '5 days offline: still authorized');
    }

    public function testServerErrorsAreNotAVerdict(): void
    {
        $this->transport->queue[] = $this->signer->answer([]);
        $this->service()->refresh();
        foreach ([500, 502, 503, 429, 403] as $code) {
            $this->transport->queue[] = ['status' => $code, 'body' => '<html>proxy</html>'];
            $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh(), (string) $code);
        }
        $this->assertSame('valid', $this->store->record['state']);
    }

    public function testExplicitUnknownCredentialLocksAnAuthorizedStore(): void
    {
        $this->transport->queue[] = $this->signer->answer([]);
        $this->service()->refresh();
        $this->assertTrue($this->allowed());
        $this->clock->now = self::NOW + 600;
        $this->transport->queue[] = ['status' => 404, 'body' => '{"error":"unknown_license_key"}'];
        $this->assertSame(StatusService::REJECTED, $this->service()->refresh());
        $this->assertSame('rejected', $this->store->record['state']);
        $this->assertFalse($this->allowed(), 'revoked: locked although the last valid answer is fresh');
    }

    public function testAnotherKindOf404IsNotAVerdict(): void
    {
        $this->transport->queue[] = $this->signer->answer([]);
        $this->service()->refresh();
        $this->transport->queue[] = ['status' => 404, 'body' => '<html>Not found (proxy)</html>'];
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->transport->queue[] = ['status' => 404, 'body' => '{"error":"unknown_license_key","x":1}'];
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
        $this->assertSame('valid', $this->store->record['state']);
    }

    public function testASignedNotValidAnswerOfAnUnboundStoreLocks(): void
    {
        $this->transport->queue[] = $this->signer->answer(['valid' => false, 'plan' => 'free', 'max_stores' => 0]);
        $this->assertSame(StatusService::REJECTED, $this->service()->refresh());
        $this->assertFalse($this->allowed());
    }

    public function testAnEndedMembershipOfAKnownStoreDoesNotLock(): void
    {
        $this->transport->queue[] = $this->signer->answer(['valid' => false, 'plan' => 'pro', 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', self::NOW - 40 * 86400)]);
        $this->assertSame(StatusService::VERIFIED, $this->service()->refresh());
        $this->assertSame('ended', $this->store->record['state']);
        $this->assertTrue($this->allowed());
    }

    public function testALapsedPaidPlanIsKeptForTheBlueNoteAndNeverLocks(): void
    {
        $ended = gmdate('Y-m-d\TH:i:s\Z', self::NOW - 5 * 86400);
        $this->transport->queue[] = $this->signer->answer(['lapsed' => ['plan' => 'pro', 'ended_at' => $ended], 'will_end' => false]);
        $this->assertSame(StatusService::VERIFIED, $this->service()->refresh());
        $this->assertSame('pro', $this->store->record['lapsed_plan']);
        $this->assertSame(self::NOW - 5 * 86400, $this->store->record['lapsed_ended_at']);
        $this->assertTrue($this->allowed());
    }

    public function testASocialOnlyAnswerCountsAsMembershipButAnUnknownProductDoesNot(): void
    {
        $this->transport->queue[] = $this->signer->answer(['product' => 'social']);
        $this->service()->refresh();
        $this->assertTrue($this->allowed());
        $this->transport->queue[] = $this->signer->answer(['product' => 'something-else']);
        $this->service()->refresh();
        $this->assertSame('rejected', $this->store->record['state']);
    }

    public function testAMalformedBodyIsNotBelieved(): void
    {
        $body = 'not json';
        $sig = base64_encode(sodium_crypto_sign_detached($body, $this->signer->secret));
        $this->transport->queue[] = ['status' => 200, 'headers' => ['x-signature' => $sig], 'body' => $body];
        $this->assertSame(StatusService::UNREACHABLE, $this->service()->refresh());
    }
}
