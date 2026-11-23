<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * What the settings card shows (pure: no Magento).
 */
class CardData
{
    /**
     * Describes the situation.
     *
     * @param AuthorizationState $state
     * @param bool $hasCredential
     * @param array $record
     * @param int $now
     * @return array
     */
    public function build(AuthorizationState $state, bool $hasCredential, array $record, int $now): array
    {
        $lapsed = null;
        if (!empty($record['lapsed_plan']) && !empty($record['lapsed_ended_at']) && $state->allowsUse()) {
            $lapsed = ['plan' => (string) $record['lapsed_plan'], 'ended' => gmdate('Y-m-d', (int) $record['lapsed_ended_at'])];
        }
        $offlineLeft = 0;
        if ($state->offline && isset($record['last_valid_at'])) {
            $offlineLeft = max(0, (int) ceil(((int) $record['last_valid_at'] + AuthorizationPolicy::OFFLINE_DAYS * 86400 - $now) / 86400));
        }
        $rejected = ($record['state'] ?? '') === RecordKeeper::REJECTED;
        return [
            'locked' => $state->isLocked(),
            'authorized' => $state->allowsUse(),
            'offline' => $state->offline,
            'offline_days_left' => $offlineLeft,
            'has_credential' => $hasCredential,
            'rejected' => $hasCredential && $rejected,
            'plan' => (string) ($record['plan'] ?? ''),
            'lapsed' => $lapsed,
        ];
    }
}
