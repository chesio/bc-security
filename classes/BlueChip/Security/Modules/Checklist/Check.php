<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

use BlueChip\Security\Helpers\Transients;

abstract class Check
{
    /**
     * @var string
     */
    const LAST_RUN_TRANSIENT_ID = 'check-last-run';

    /**
     * @var string
     */
    const RESULT_TRANSIENT_ID = 'check-result';


    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int Timestamp of last run.
     */
    private $last_run;

    /**
     * @var \BlueChip\Security\Modules\Checklist\CheckResult Result of last run.
     */
    private $result;


    /**
     * Construct the check.
     *
     * @param string $name
     * @param string $description
     */
    protected function __construct(string $name, string $description)
    {
        $this->name = $name;
        $this->description = $description;
        $this->last_run = Transients::getForSite(self::LAST_RUN_TRANSIENT_ID, self::getId()) ?: 0;
        $this->result = Transients::getForSite(self::RESULT_TRANSIENT_ID, self::getId()) ?: new CheckResult(null, '<em>' . esc_html__('Check has not been run yet or the bookkeeping data has been lost.', 'bc-security') . '</em>');
    }


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
    public function getDescription(): string
    {
        return $this->description;
    }


    /**
     * @return string Check name (title).
     */
    public function getName(): string
    {
        return $this->name;
    }


    /**
     * @return int Timestamp of last run or 0, if no info about last run is available.
     */
    public function getTimeOfLastRun(): int
    {
        return $this->last_run;
    }


    /**
     * @return \BlueChip\Security\Modules\Checklist\CheckResult Result of the most recent check (possibly cached).
     */
    public function getResult(): CheckResult
    {
        return $this->result;
    }


    /**
     * By default, every check makes sense.
     *
     * @return bool
     */
    public function makesSense(): bool
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
        $this->last_run = current_time('timestamp');
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
