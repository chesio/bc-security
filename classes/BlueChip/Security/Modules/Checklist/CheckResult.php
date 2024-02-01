<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Checklist;

class CheckResult
{
    private ?bool $status;

    /**
     * @var string[] Human readable message explaining the result as list of (hyper)text lines.
     */
    private array $message;


    /**
     * @param bool|null $status Check result status: false if check failed; true if check passed; null for undetermined status.
     * @param string|string[] $message Human readable message explaining the result - inline HTML tags are allowed/expected.
     */
    public function __construct(?bool $status, array|string $message)
    {
        $this->status = $status;
        $this->message = \is_array($message) ? $message : [$message];
    }


    /**
     * @return string[] Human readable message as list of (hyper)text lines.
     */
    public function getMessage(): array
    {
        return $this->message;
    }


    /**
     * @return string Human readable message as single string with HTML tags.
     */
    public function getMessageAsHtml(): string
    {
        return \implode('<br>', $this->message);
    }


    /**
     * @return string Human readable message as single string without HTML tags.
     */
    public function getMessageAsPlainText(): string
    {
        return \strip_tags(\implode(PHP_EOL, $this->message));
    }


    /**
     * @return bool|null Check result status: false if check failed; true if check passed; null means status is undetermined.
     */
    public function getStatus(): ?bool
    {
        return $this->status;
    }
}
