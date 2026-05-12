<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event;

use Psr\Http\Message\ServerRequestInterface;

final class CategoryListActionEvent
{
    /**
     * @var mixed[]
     */
    private array $values;

    private ServerRequestInterface $request;

    /**
     * @var mixed[]
     */
    private array $settings;

    /**
     * @param mixed[] $values
     * @param mixed[] $settings
     */
    public function __construct(array $values, ServerRequestInterface $request, array $settings)
    {
        $this->values = $values;
        $this->request = $request;
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

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
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
