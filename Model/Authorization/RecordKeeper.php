<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * Turns an answer of the website into the small record the store keeps (and the other way round for the settings card).
 */
class RecordKeeper
{
    public const VALID = 'valid';
    public const ENDED = 'ended';
    public const REJECTED = 'rejected';

    /** how long an answer that says "not bound" still looks like the first activation (same rule as the other plugins) */
    private const UNBOUND_GRACE_SECONDS = 300;

    /**
     * Record for a verified signed answer.
     *
     * @param array $previous the record before
     * @param Answer $answer
     * @param int $now
     * @return array
     */
    public function fromAnswer(array $previous, Answer $answer, int $now): array
    {
        $rejected = !$answer->valid && ($answer->plan === 'none' || $answer->expiresAt > $now - self::UNBOUND_GRACE_SECONDS);
        $state = $answer->valid ? self::VALID : ($rejected ? self::REJECTED : self::ENDED);
        return [
            'state' => $state,
            'answer_at' => $now,
            'last_valid_at' => $answer->valid ? $now : ($previous['last_valid_at'] ?? null),
            'plan' => $answer->plan,
            'expires_at' => $answer->expiresAt,
            'lapsed_plan' => $answer->lapsedPlan,
            'lapsed_ended_at' => $answer->lapsedEndedAt,
        ];
    }

    /**
     * Record for the website's clear "unknown credential / store removed / revoked" answer.
     *
     * @param array $previous
     * @param int $now
     * @return array
     */
    public function rejected(array $previous, int $now): array
    {
        return [
            'state' => self::REJECTED,
            'answer_at' => $now,
            'last_valid_at' => $previous['last_valid_at'] ?? null,
            'plan' => 'none',
            'expires_at' => 0,
            'lapsed_plan' => null,
            'lapsed_ended_at' => null,
        ];
    }
}
