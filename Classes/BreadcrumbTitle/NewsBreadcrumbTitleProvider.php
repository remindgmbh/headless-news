<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\BreadcrumbTitle;

use Remind\Headless\BreadcrumbTitle\AbstractBreadcrumbTitleProvider;

class NewsBreadcrumbTitleProvider extends AbstractBreadcrumbTitleProvider
{
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
}
