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


    public function __construct($status, string $message = '') {
        $this->status = $status;
        $this->message = $message;
    }


    public function getMessage(): string
    {
        return $this->message;
    }


    public function getStatus()
    {
        return $this->status;
    }
}
