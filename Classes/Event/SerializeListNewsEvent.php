<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event;

final class SerializeListNewsEvent
{
    /**
     * @var mixed[]
     */
    private array $values;

    /**
     * @param mixed[] $values
     */
    public function __construct(array $values)
    {
        $this->values = $values;
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
}
