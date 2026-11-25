<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Test\Unit\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\Authorization\Clock;
use GetDropFlow\FreeShippingBar\Model\Authorization\Domain;
use GetDropFlow\FreeShippingBar\Model\Authorization\Store;
use GetDropFlow\FreeShippingBar\Model\Authorization\Transport;

class MemoryStore implements Store
{
    public $credential = '';
    public $record = [];
    public $pending;

    public function credential(): string
    {
        return $this->credential;
    }

    public function saveCredential(string $credential): void
    {
        $this->credential = $credential;
    }

    public function forget(): void
    {
        $this->credential = '';
        $this->record = [];
        $this->pending = null;
    }

    public function record(): array
    {
        return $this->record;
    }

    public function saveRecord(array $record): void
    {
        $this->record = $record;
    }

    public function pending(): ?array
    {
        return $this->pending;
    }

    public function savePending(?array $pending): void
    {
        $this->pending = $pending;
    }
}

class FixedClock extends Clock
{
    public $now;

    public function __construct(int $now = 1800000000)
    {
        $this->now = $now;
    }

    public function now(): int
    {
        return $this->now;
    }
}

class FixedDomain implements Domain
{
    public function get(): string
    {
        return 'shop.example.com';
    }
}

/** Answers from a queue; each item is an array (status, headers, body) or a Throwable that is thrown. Remembers the requests. */
class FakeTransport implements Transport
{
    public $queue = [];
    public $requests = [];

    public function send(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $this->requests[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'body' => $body];
        $next = array_shift($this->queue);
        if ($next === null || $next instanceof \Throwable) {
            throw $next ?? new \RuntimeException('no answer queued');
        }
        return $next + ['headers' => []];
    }
}

/** A signing key pair and signed answers, like the license server's. */
class Signer
{
    public $secret;
    public $public;

    public function __construct()
    {
        $pair = sodium_crypto_sign_keypair();
        $this->secret = sodium_crypto_sign_secretkey($pair);
        $this->public = base64_encode(sodium_crypto_sign_publickey($pair));
    }

    public function answer(array $fields, ?int $issuedAt = null, bool $sign = true, ?string $badSignatureFor = null): array
    {
        $now = $issuedAt ?? 1800000000;
        $body = json_encode($fields + [
            'valid' => true, 'plan' => 'free', 'domain' => 'shop.example.com', 'max_stores' => 1, 'max_products' => 10,
            'issued_at' => gmdate('Y-m-d\TH:i:s\Z', $now), 'expires_at' => gmdate('Y-m-d\TH:i:s\Z', $now + 86400 * 365), 'features' => [],
        ]);
        $sig = base64_encode(sodium_crypto_sign_detached($badSignatureFor ?? $body, $this->secret));
        return ['status' => 200, 'headers' => $sign ? ['x-signature' => $sig] : [], 'body' => $body];
    }
}
