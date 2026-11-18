<?php
/**
 * Copyright (c) GetDropFlow. Licensed under the MIT License.
 */
declare(strict_types=1);

namespace GetDropFlow\FreeShippingBar\Model\Upgrade;

/**
 * Runs the steps of an upgrade in order. When a step fails, every step that was started is undone in the opposite
 * order, then the store is rebuilt on the old code. If even that fails the result is "needs_manual": better a
 * stopped shop than half old, half new code.
 */
class UpgradeRunner
{
    /** @var UpgradeStatus */
    private $status;

    /**
     * @param UpgradeStatus $status
     */
    public function __construct(UpgradeStatus $status)
    {
        $this->status = $status;
    }

    /**
     * Runs the upgrade.
     *
     * @param UpgradeStep[] $steps
     * @param callable $rebuild called after a rollback to make the shop run on the old code again
     * @param callable $finish always called at the end (for example: leave maintenance mode)
     * @param array $extra extra status fields
     * @return string resulting state: done, failed (nothing changed), rolled_back or needs_manual
     */
    public function run(array $steps, callable $rebuild, callable $finish, array $extra = []): string
    {
        $total = count($steps);
        $started = [];
        try {
            foreach (array_values($steps) as $i => $step) {
                $this->status->write(UpgradeStatus::RUNNING, $i + 1, $total, $step->label(), '', $extra);
                $started[] = $step;
                $step->run();
            }
            $this->finishQuietly($finish);
            $this->status->write(UpgradeStatus::DONE, $total, $total, '', '', $extra);
            return UpgradeStatus::DONE;
        } catch (\Throwable $e) {
            return $this->rollBack($started, $rebuild, $finish, $e, $total, $extra);
        }
    }

    /**
     * Undoes the started steps in reverse order.
     *
     * @param UpgradeStep[] $started
     * @param callable $rebuild
     * @param callable $finish
     * @param \Throwable $cause
     * @param int $total
     * @param array $extra
     * @return string
     */
    private function rollBack(
        array $started,
        callable $rebuild,
        callable $finish,
        \Throwable $cause,
        int $total,
        array $extra
    ): string {
        $reason = $cause instanceof UpgradeRejected ? $cause->reason : 'STEP_FAILED';
        $extra['reason'] = $reason;
        $extra['detail'] = substr(trim($cause->getMessage()), 0, 500);
        $this->status->log(get_class($cause) . ' [' . $reason . '] ' . $extra['detail']);
        // The shop is only rebuilt when a step that changes its code or generated files was started. A failure
        // before that (download, checksum, unpack, maintenance switch) changes nothing, so nothing is rebuilt.
        $changed = false;
        foreach ($started as $step) {
            $changed = $changed || $step instanceof ChangesShop;
        }
        $extra['changed'] = $changed;
        $ok = true;
        foreach (array_reverse($started) as $step) {
            try {
                $step->undo();
            } catch (\Throwable $undoError) {
                $ok = false;
            }
        }
        if ($changed) {
            try {
                $rebuild();
            } catch (\Throwable $rebuildError) {
                $ok = false;
            }
        }
        if (!$ok) {
            // maintenance mode is kept on purpose: the shop must not run on half-restored code
            $extra['changed'] = true;
            $this->status->write(
                UpgradeStatus::NEEDS_MANUAL,
                $total,
                $total,
                '',
                'The upgrade failed and the automatic restore did not finish. Follow the manual restore steps.',
                $extra
            );
            return UpgradeStatus::NEEDS_MANUAL;
        }
        $this->finishQuietly($finish);
        if (!$changed) {
            $this->status->write(
                UpgradeStatus::FAILED,
                $total,
                $total,
                '',
                'The upgrade stopped before anything on the shop was changed.',
                $extra
            );
            return UpgradeStatus::FAILED;
        }
        $this->status->write(
            UpgradeStatus::ROLLED_BACK,
            $total,
            $total,
            '',
            'The upgrade did not work; your previous version was put back.',
            $extra
        );
        return UpgradeStatus::ROLLED_BACK;
    }

    /**
     * Runs the finish callback; a failure here must not hide the real result.
     *
     * @param callable $finish
     * @return void
     */
    private function finishQuietly(callable $finish): void
    {
        try {
            $finish();
        } catch (\Throwable $e) {
            return;
        }
    }
}
