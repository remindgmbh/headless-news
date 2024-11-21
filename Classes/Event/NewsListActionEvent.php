<?php

namespace Remind\HeadlessNews\Event;

final class NewsListActionEvent
{
    /**
     * @var array
     */
    private $values;

    /**
     * @var array
     */
    private $settings;

    public function __construct(array $values, array $settings)
    {
        $this->values = $values;
        $this->settings = $settings;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): self
    {
        $this->values = $values;

        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): self
    {
        $this->settings = $settings;

        return $this;
    }
}
