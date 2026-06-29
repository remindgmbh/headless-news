<?php

declare(strict_types=1);

namespace Remind\HeadlessNews\Tests\Unit\Controller;

final class DateMenuViewDouble
{
    /**
     * @var array<string, mixed>
     */
    private array $assignedValues = [];

    /**
     * @param array<string, mixed> $values
     */
    public function assignMultiple(array $values): void
    {
        $this->assignedValues = $values;
    }

    public function getRenderingContext(): object
    {
        return new class ($this) {
            private DateMenuViewDouble $viewDouble;

            public function __construct(DateMenuViewDouble $viewDouble)
            {
                $this->viewDouble = $viewDouble;
            }

            public function getVariableProvider(): object
            {
                return new class ($this->viewDouble) {
                    private DateMenuViewDouble $viewDouble;

                    public function __construct(DateMenuViewDouble $viewDouble)
                    {
                        $this->viewDouble = $viewDouble;
                    }

                    /**
                     * @return array<string, mixed>
                     */
                    public function getAll(): array
                    {
                        return $this->viewDouble->getAssignedValues();
                    }
                };
            }
        };
    }

    public function render(): string
    {
        return '';
    }

    /**
     * @return array<string, mixed>
     */
    public function getAssignedValues(): array
    {
        return $this->assignedValues;
    }
}
