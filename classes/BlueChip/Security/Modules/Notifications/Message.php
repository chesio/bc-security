<?php

declare(strict_types=1);

namespace BlueChip\Security\Modules\Notifications;

class Message
{
    /**
     * @var string[] Message body as lines of text
     */
    private array $body = [];


    public function __construct(string $text = '')
    {
        $this->body = ($text !== '') ? [$text] : [];
    }


    public function __toString(): string
    {
        return \implode(PHP_EOL, $this->body);
    }


    public function addEmptyLine(): self
    {
        return $this->addLine('');
    }


    public function addLine(string $text = ''): self
    {
        $this->body[] = $text;
        return $this;
    }


    /**
     * @param string[] $text
     *
     * @return self
     */
    public function addLines(array $text): self
    {
        $this->body = \array_merge($this->body, \array_is_list($text) ? $text : \array_values($text));
        return $this;
    }


    public function getFingerprint(): string
    {
        return \sha1((string) $this);
    }


    /**
     * @return string[]
     */
    public function getRaw(): array
    {
        return $this->body;
    }
}
