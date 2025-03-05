<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Event;

use GeorgRinger\News\Domain\Model\News;

final class SerializeNewsEvent
{
    private News $news;

    /**
     * @var mixed[]
     */
    private array $values;

    /**
     * @param mixed[] $values
     */
    public function __construct(News $news, array $values)
    {
        $this->news = $news;
        $this->values = $values;
    }

    public function getNews(): News
    {
        return $this->news;
    }

    public function setNews(News $news): self
    {
        $this->news = $news;

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
