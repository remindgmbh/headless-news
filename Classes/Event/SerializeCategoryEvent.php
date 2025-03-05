<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event;

use GeorgRinger\News\Domain\Model\Category;

final class SerializeCategoryEvent
{
    private Category $category;

    /**
     * @var mixed[]
     */
    private array $values;

    /**
     * Summary of __construct
     * @param mixed[] $values
     */
    public function __construct(Category $category, array $values)
    {
        $this->category = $category;
        $this->values = $values;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
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
