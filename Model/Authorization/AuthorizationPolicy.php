<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * "An account must authorize the store before the plugin works." Same rule as the other GetDropFlow plugins for Magento
 * (dropshipkit src/License/AuthorizationPolicy, task web/011), without the grace period for old stores: this plugin has
 * no existing users, so a new store must authorize before the bar shows. Facts only, no network, no Magento.
 *
 *  AUTHORIZED    linked to an account (valid signed answer, a known store whose membership ended, or valid a short while ago and the website only unreachable)
 *  LOCKED        not authorized: only the authorization card is shown and the bar is hidden on the storefront
 *  NOT_ENFORCED  the rule does not apply
 *
 * A free membership is a membership. Only the website's clear "unknown / revoked" answer locks an authorized store; a website that is down never does.
 */
class AuthorizationPolicy
{
    public const AUTHORIZED = 'authorized';
    public const LOCKED = 'locked';
    public const NOT_ENFORCED = 'not_enforced';

    public const OFFLINE_DAYS = 30;

    /**
     * Decides the state.
     *
     * @param bool $enforced false = the rule does not apply
     * @param bool $validNow the membership answer the plugin uses now is valid
     * @param bool $linkedButEnded the server knows the store but the membership is not valid (ended, cancelled)
     * @param bool $rejected the server clearly answered "unknown / not bound / revoked"
     * @param int|null $lastValidAt time of the last verified valid answer (null = none)
     * @param int $now
     * @return AuthorizationState
     */
    public static function decide(
        bool $enforced,
        bool $validNow,
        bool $linkedButEnded,
        bool $rejected,
        ?int $lastValidAt,
        int $now
    ): AuthorizationState {
        if (!$enforced) {
            return new AuthorizationState(self::NOT_ENFORCED);
        }
        if ($validNow || $linkedButEnded) {
            return new AuthorizationState(self::AUTHORIZED);
        }
        if (!$rejected && $lastValidAt !== null && $now >= $lastValidAt
            && $now - $lastValidAt <= self::OFFLINE_DAYS * 86400) {
            return new AuthorizationState(self::AUTHORIZED, true);
        }
        return new AuthorizationState(self::LOCKED);
    }
}
