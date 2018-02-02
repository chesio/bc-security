<?php
/**
 * @package BC_Security
 */

namespace BlueChip\Security\Modules\Checklist;

abstract class Check
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;


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
     */
    abstract public function run(): CheckResult;
}
