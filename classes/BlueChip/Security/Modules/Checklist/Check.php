<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\Transients;

abstract class Check
{
    /**
     * @var string
     */
    private const LAST_RUN_TRANSIENT_ID = 'check-last-run';

    /**
     * @var string
     */
    private const RESULT_TRANSIENT_ID = 'check-result';

    /**
     * @var int|null Timestamp of last run if lazy-loaded already, null otherwise.
     */
    private ?int $last_run = null;

    /**
     * @var CheckResult|null Result of last run if lazy-loaded already, null otherwise.
     */
    private ?CheckResult $result = null;


    /**
     * @return string Check unique ID. Basically name of class implementing the check.
     */
    public static function getId(): string
    {
        return static::class;
    }


    /**
     * @return string Check description.
     */
    abstract public function getDescription(): string;


    /**
     * @return string Check name (title).
     */
    abstract public function getName(): string;


    /**
     * @return int Timestamp of last run or 0 if no info about last run is available.
     */
    public function getTimeOfLastRun(): int
    {
        if ($this->last_run === null) {
            $this->last_run = (int) (Transients::getForSite(self::LAST_RUN_TRANSIENT_ID, self::getId()) ?: 0);
        }

        return $this->last_run;
    }


    /**
     * @return \BlueChip\Security\Modules\Checklist\CheckResult Result of the most recent check (possibly cached).
     */
    public function getResult(): CheckResult
    {
        if ($this->result === null) {
            $this->result = Transients::getForSite(self::RESULT_TRANSIENT_ID, self::getId()) ?: new CheckResult(null, '<em>' . esc_html__('Check has not been run yet or the bookkeeping data has been lost.', 'bc-security') . '</em>');
        }

        return $this->result;
    }


    /**
     * By default, every check is meaningful.
     *
     * @return bool
     */
    public function isMeaningful(): bool
    {
        return true;
    }


    /**
     * Perform the check.
     *
     * @internal Method is a wrapper around runInternal() method - it stores the result internally and as transient.
     *
     * @return \BlueChip\Security\Modules\Checklist\CheckResult
     */
    public function run(): CheckResult
    {
        // Run the check...
        $this->last_run = \time();
        $this->result = $this->runInternal();
        // ... cache the time and result...
        Transients::setForSite($this->last_run, self::LAST_RUN_TRANSIENT_ID, self::getId());
        Transients::setForSite($this->result, self::RESULT_TRANSIENT_ID, self::getId());
        // ...and return it.
        return $this->result;
    }


    /**
     * Perform the check.
     */
    abstract protected function runInternal(): CheckResult;
}
