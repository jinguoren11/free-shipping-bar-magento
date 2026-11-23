<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * "Log in and authorize": POST /v1/link/start {domain, product, lang} gives a code pair; the merchant allows the store on the
 * website (signed in to his account); POST /v1/link/poll {device_code} then hands out the store's own site token once.
 * Only https, only the store's own domain. The answers are not signed: the token is checked by the signed status answer that is
 * asked for right after (a token that is not the server's fails there and does not authorize anything).
 */
class LinkService
{
    public const WAITING = 'waiting';
    public const LINKED = 'linked';
    public const LINKED_UNVERIFIED = 'linked_unverified';
    public const NONE = 'none';
    public const DENIED = 'denied';
    public const EXPIRED = 'expired';
    public const UNREACHABLE = 'unreachable';
    public const FAILED = 'failed';
    public const NOT_CONFIGURED = 'not_configured';
    public const RATE_LIMITED = 'rate_limited';

    private const CODE_SHAPE = '/^[A-Za-z0-9_-]{20,200}$/';
    private const USER_CODE_SHAPE = '/^[BCDFGHJKLMNPQRSTVWXZ2-9]{4}-[BCDFGHJKLMNPQRSTVWXZ2-9]{4}$/';
    private const CREDENTIAL_SHAPE = '/^[A-Za-z0-9._-]{10,200}$/';

    /**
     * @var Store
     */
    private $store;

    /**
     * @var Transport
     */
    private $transport;

    /**
     * @var Domain
     */
    private $domain;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var StatusService
     */
    private $status;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @param Store $store
     * @param Transport $transport
     * @param Domain $domain
     * @param Clock $clock
     * @param StatusService $status
     * @param string $baseUrl
     */
    public function __construct(
        Store $store,
        Transport $transport,
        Domain $domain,
        Clock $clock,
        StatusService $status,
        string $baseUrl = ServerKey::BASE_URL
    ) {
        $this->store = $store;
        $this->transport = $transport;
        $this->domain = $domain;
        $this->clock = $clock;
        $this->status = $status;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Starts an attempt.
     *
     * @param string $lang two letter language of the admin
     * @return array result and, when waiting, user_code, url, expires_in, interval
     */
    public function begin(string $lang): array
    {
        $domain = $this->domain->get();
        if ($domain === '' || strpos($this->baseUrl, 'https://') !== 0) {
            return ['result' => self::NOT_CONFIGURED];
        }
        $r = $this->post('/v1/link/start', [
            'domain' => $domain,
            'product' => ServerKey::PRODUCT,
            'lang' => preg_match('/^[a-z]{2}$/', $lang) === 1 ? $lang : 'en',
        ]);
        if ($r['status'] === 0) {
            return ['result' => self::UNREACHABLE];
        }
        if ($r['status'] === 429) {
            return ['result' => self::RATE_LIMITED];
        }
        $d = $r['data'];
        $url = $d['verification_url'] ?? null;
        if ($r['status'] !== 200 || !is_string($d['device_code'] ?? null) || preg_match(self::CODE_SHAPE, $d['device_code']) !== 1
            || !is_string($d['user_code'] ?? null) || preg_match(self::USER_CODE_SHAPE, $d['user_code']) !== 1
            || !is_string($url) || strpos($url, 'https://') !== 0 || preg_match('/\s/', $url) === 1) {
            return ['result' => self::FAILED];
        }
        $expires = is_int($d['expires_in'] ?? null) ? max(30, min(1800, $d['expires_in'])) : 600;
        $interval = is_int($d['interval'] ?? null) ? max(2, min(30, $d['interval'])) : 3;
        $this->store->savePending([
            'device_code' => $d['device_code'],
            'user_code' => $d['user_code'],
            'url' => $url,
            'expires' => $this->clock->now() + $expires,
            'interval' => $interval,
        ]);
        return ['result' => self::WAITING, 'user_code' => $d['user_code'], 'url' => $url, 'expires_in' => $expires, 'interval' => $interval];
    }

    /**
     * The waiting attempt for the settings page (an attempt whose time is up is forgotten), or null.
     *
     * @return array|null
     */
    public function pending(): ?array
    {
        $p = $this->store->pending();
        if ($p === null) {
            return null;
        }
        $left = (int) $p['expires'] - $this->clock->now();
        if ($left <= 0) {
            $this->store->savePending(null);
            return null;
        }
        return ['user_code' => $p['user_code'], 'url' => $p['url'], 'expires_in' => $left, 'interval' => $p['interval']];
    }

    /**
     * Asks whether the merchant has allowed the store yet.
     *
     * @return array result
     */
    public function poll(): array
    {
        $p = $this->store->pending();
        if ($p === null) {
            return ['result' => self::NONE];
        }
        if ((int) $p['expires'] <= $this->clock->now()) {
            $this->store->savePending(null);
            return ['result' => self::EXPIRED];
        }
        $r = $this->post('/v1/link/poll', ['device_code' => (string) $p['device_code']]);
        if ($r['status'] === 0 || $r['status'] === 429 || $r['status'] >= 500) {
            return ['result' => self::UNREACHABLE]; // transient: the attempt stays and the page asks again
        }
        if ($r['status'] === 410 || $r['status'] === 404) {
            $this->store->savePending(null);
            return ['result' => self::EXPIRED];
        }
        if ($r['status'] !== 200) {
            $this->store->savePending(null);
            return ['result' => self::FAILED];
        }
        $status = $r['data']['status'] ?? null;
        if ($status === 'pending') {
            return ['result' => self::WAITING];
        }
        if ($status === 'denied') {
            $this->store->savePending(null);
            return ['result' => self::DENIED];
        }
        $token = $r['data']['license_key'] ?? null;
        $this->store->savePending(null); // the server hands the credential out once: from here the attempt is over whatever happens
        if ($status !== 'approved' || !is_string($token) || preg_match(self::CREDENTIAL_SHAPE, $token) !== 1) {
            return ['result' => self::FAILED];
        }
        $this->store->saveCredential($token);
        $outcome = $this->status->refresh();
        return ['result' => $outcome === StatusService::VERIFIED ? self::LINKED : self::LINKED_UNVERIFIED];
    }

    /**
     * Gives up the waiting attempt.
     *
     * @return void
     */
    public function cancel(): void
    {
        $this->store->savePending(null);
    }

    /**
     * Advanced: saves a typed key and checks it at once.
     *
     * @param string $key
     * @return string LINKED, LINKED_UNVERIFIED or FAILED
     */
    public function saveTypedKey(string $key): string
    {
        $key = trim($key);
        if (preg_match(self::CREDENTIAL_SHAPE, $key) !== 1) {
            return self::FAILED;
        }
        $this->store->saveCredential($key);
        return $this->status->refresh() === StatusService::VERIFIED ? self::LINKED : self::LINKED_UNVERIFIED;
    }

    /**
     * One POST with a JSON body.
     *
     * @param string $path
     * @param array $body
     * @return array{status:int,data:array}
     */
    private function post(string $path, array $body): array
    {
        try {
            $reply = $this->transport->send(
                'POST',
                rtrim($this->baseUrl, '/') . $path,
                ['Content-Type' => 'application/json'],
                (string) json_encode($body)
            );
        } catch (\Throwable $e) {
            return ['status' => 0, 'data' => []];
        }
        $data = json_decode($reply['body'], true);
        return ['status' => $reply['status'], 'data' => is_array($data) ? $data : []];
    }
}
