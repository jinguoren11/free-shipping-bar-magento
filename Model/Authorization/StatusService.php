<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Asks the license server about this store (GET /v1/license/status) and keeps the verified answer. Called by the cron job
 * and by the "Check now" button, never by the storefront. A website that cannot be reached changes nothing; only a signed
 * answer or the server's own "unknown credential" answer changes what the store believes.
 */
class StatusService
{
    public const VERIFIED = 'verified';
    public const REJECTED = 'rejected';
    public const UNREACHABLE = 'unreachable';
    public const NO_CREDENTIAL = 'no_credential';

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
     * @var AnswerParser
     */
    private $parser;

    /**
     * @var RecordKeeper
     */
    private $keeper;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @param Store $store
     * @param Transport $transport
     * @param Domain $domain
     * @param Clock $clock
     * @param AnswerParser $parser
     * @param RecordKeeper $keeper
     * @param string $baseUrl
     * @param string $publicKey
     */
    public function __construct(
        Store $store,
        Transport $transport,
        Domain $domain,
        Clock $clock,
        AnswerParser $parser,
        RecordKeeper $keeper,
        string $baseUrl = ServerKey::BASE_URL,
        string $publicKey = ServerKey::PUBLIC_KEY_BASE64
    ) {
        $this->store = $store;
        $this->transport = $transport;
        $this->domain = $domain;
        $this->clock = $clock;
        $this->parser = $parser;
        $this->keeper = $keeper;
        $this->baseUrl = $baseUrl;
        $this->publicKey = $publicKey;
    }

    /**
     * Asks now and keeps the answer.
     *
     * @return string one of the class constants
     */
    public function refresh(): string
    {
        $credential = $this->store->credential();
        $domain = $this->domain->get();
        if ($credential === '' || $domain === '') {
            return self::NO_CREDENTIAL;
        }
        try {
            $reply = $this->transport->send(
                'GET',
                rtrim($this->baseUrl, '/') . '/v1/license/status?domain=' . rawurlencode($domain),
                ['X-License-Key' => $credential]
            );
        } catch (\Throwable $e) {
            return self::UNREACHABLE;
        }
        $now = $this->clock->now();
        if ($reply['status'] === 404 && $this->isUnknownCredentialAnswer($reply['body'])) {
            $this->store->saveRecord($this->keeper->rejected($this->store->record(), $now));
            return self::REJECTED;
        }
        if ($reply['status'] !== 200) {
            return self::UNREACHABLE;
        }
        $answer = $this->parser->parse($reply['body'], $reply['headers']['x-signature'] ?? null, $domain, $now, $this->publicKey);
        if ($answer === null) {
            return self::UNREACHABLE; // not signed by the website: it counts as "cannot ask", never as a verdict
        }
        $record = $this->keeper->fromAnswer($this->store->record(), $answer, $now);
        $this->store->saveRecord($record);
        return $record['state'] === RecordKeeper::REJECTED ? self::REJECTED : self::VERIFIED;
    }

    /**
     * The server's own "unknown credential" answer is not signed, so it is trusted only over https and in exactly its form.
     * The page of a proxy, a firewall or a tunnel is "cannot ask the server", not a verdict.
     *
     * @param string $body
     * @return bool
     */
    private function isUnknownCredentialAnswer(string $body): bool
    {
        if (strpos(strtolower($this->baseUrl), 'https://') !== 0) {
            return false;
        }
        $json = json_decode($body, true);
        return is_array($json) && $json === ['error' => 'unknown_license_key'];
    }
}
