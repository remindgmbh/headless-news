<?php

namespace Remind\HeadlessNews\Event;

use GeorgRinger\News\Domain\Model\Category;

final class SerializeCategoryEvent
{
    /**
     * @var Category
     */
    private $category;

    /**
     * @var array
     */
    private $values;

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
