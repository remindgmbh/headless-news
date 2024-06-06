<?php

namespace Remind\HeadlessNews\Event;

use GeorgRinger\News\Domain\Model\News;

final class SerializeNewsEvent
{
    /**
     * @var News
     */
    private $news;

    /**
     * @var array
     */
    private $values;

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
