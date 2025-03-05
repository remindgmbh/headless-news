<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event;

final class NewsListActionEvent
{
    /**
     * @var mixed[]
     */
    private array $values;

    /**
     * @var mixed[]
     */
    private array $settings;

    /**
     * @param mixed[] $values
     * @param mixed[] $settings
     */
    public function __construct(array $values, array $settings)
    {
        $this->values = $values;
        $this->settings = $settings;
    }

    /**
     * @return mixed[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param mixed[] $values
     */
    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    /**
     * @return mixed[]
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param mixed[] $settings
     */
    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }
}
