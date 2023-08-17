<?php

namespace BlueChip\Security\Modules\BadRequestsBanner;

class BanRule
{
    /**
     * Construct the rule.
     */
    public function __construct(private string $name, private string $pattern, private string $description = '')
    {
    }

    /**
     * @return string Rule description (is optional)
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string Rule name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $uri URI to match against rule
     *
     * @return bool True if rule matches given URI, false otherwise.
     */
    public function matches(string $uri): bool
    {
        $pattern = sprintf('/%s/i', $this->pattern);

        return (bool) preg_match($pattern, $uri);
    }
}
