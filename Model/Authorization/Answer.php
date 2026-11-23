<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Authorization;

/**
 * What the license server said about this store (already checked: signature, domain, age).
 */
class Answer
{
    /** @var bool */
    public $valid;
    /** @var string */
    public $plan;
    /** @var int */
    public $expiresAt;
    /** @var int */
    public $maxStores;
    /** @var string|null plan that ended (a lapsed paid period), null = none */
    public $lapsedPlan;
    /** @var int|null */
    public $lapsedEndedAt;

    /**
     * @param bool $valid
     * @param string $plan
     * @param int $expiresAt
     * @param int $maxStores
     * @param string|null $lapsedPlan
     * @param int|null $lapsedEndedAt
     */
    public function __construct(
        bool $valid,
        string $plan,
        int $expiresAt,
        int $maxStores,
        ?string $lapsedPlan = null,
        ?int $lapsedEndedAt = null
    ) {
        $this->valid = $valid;
        $this->plan = $plan;
        $this->expiresAt = $expiresAt;
        $this->maxStores = $maxStores;
        $this->lapsedPlan = $lapsedPlan;
        $this->lapsedEndedAt = $lapsedEndedAt;
    }
}
