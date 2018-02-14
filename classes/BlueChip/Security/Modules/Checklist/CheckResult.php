<?php

namespace BlueChip\Security\Modules\Checklist;

class CheckResult
{
    /**
     * @var bool|null
     */
    private $status;

    /**
     * @var string
     */
    private $message;


    /**
     * @param bool|null $status Check result status: false, if check failed; true, if check passed; null for undetermined status.
     * @param string $message Human readable message explaining the result - HTML tags are allowed/expected.
     */
    public function __construct($status, string $message = '')
    {
        $this->status = $status;
        $this->message = $message;
    }


    /**
     * @return string Human readable message explaining the result - may contain HTML tags!
     */
    public function getMessage(): string
    {
        return $this->message;
    }


    /**
     * @return bool|null Check result status: false, if check failed; true, if check passed; null means status is undetermined.
     */
    public function getStatus()
    {
        return $this->status;
    }
}
