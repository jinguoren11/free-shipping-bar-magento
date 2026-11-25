<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

use GetDropFlow\FreeShippingBar\Model\AccessCheck;

/**
 * Is this store authorized (linked to a GetDropFlow account)? Builds the facts for AuthorizationPolicy from what the plugin
 * has saved (credential, last signed answer). Never asks the network: the storefront and the settings page call this.
 */
class AuthorizationGate implements AccessCheck
{
    /**
     * @var Store
     */
    private $store;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var AuthorizationState|null
     */
    private $state;

    /**
     * @param Store $store
     * @param Clock $clock
     */
    public function __construct(Store $store, Clock $clock)
    {
        $this->store = $store;
        $this->clock = $clock;
    }

    /**
     * The state now.
     *
     * @return AuthorizationState
     */
    public function state(): AuthorizationState
    {
        if ($this->state === null) {
            $this->state = $this->compute();
        }
        return $this->state;
    }

    /**
     * Forgets the remembered state (after the credential or the record changed).
     *
     * @return void
     */
    public function forget(): void
    {
        $this->state = null;
    }

    /**
     * @inheritDoc
     */
    public function isAllowed(): bool
    {
        return $this->state()->allowsUse();
    }

    /**
     * Works the state out.
     *
     * @return AuthorizationState
     */
    private function compute(): AuthorizationState
    {
        $now = $this->clock->now();
        if ($this->store->credential() === '') {
            return AuthorizationPolicy::decide(true, false, false, false, null, $now);
        }
        $r = $this->store->record();
        $state = (string) ($r['state'] ?? '');
        $answerAt = isset($r['answer_at']) ? (int) $r['answer_at'] : 0;
        $lastValid = isset($r['last_valid_at']) ? (int) $r['last_valid_at'] : null;
        $valid = $state === RecordKeeper::VALID && $now >= $answerAt && $now - $answerAt <= ServerKey::MAX_AGE_SECONDS;
        return AuthorizationPolicy::decide(
            true,
            $valid,
            $state === RecordKeeper::ENDED,
            $state === RecordKeeper::REJECTED,
            $lastValid,
            $now
        );
    }
}
