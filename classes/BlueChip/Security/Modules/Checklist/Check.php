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
    const CHECK_CLASS = '';

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
     * @param string $classname
     * @return string ID of check computed from given classname.
     */
    public static function getCheckId(string $classname): string
    {
        return basename(str_replace('\\', '/', $classname));
    }


    /**
     * @return string Check unique ID.
     */
    public static function getId(): string
    {
        return self::getCheckId(static::class);
    }


    /**
     * @return string Class of check.
     */
    public static function getClass(): string
    {
        return static::CHECK_CLASS;
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
     *
     * @return \BlueChip\Security\Modules\Checklist\CheckResult
     */
    abstract public function run(): CheckResult;
}
