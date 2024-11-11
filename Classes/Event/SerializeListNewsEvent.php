<?php

namespace Remind\HeadlessNews\Event;

use GeorgRinger\News\Domain\Model\News;

final class SerializeListNewsEvent
{
    /**
     * @var array
     */
    private $values;

    public function __construct(array $values)
    {
        $this->values = $values;
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
}
