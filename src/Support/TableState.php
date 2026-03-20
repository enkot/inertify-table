<?php

declare(strict_types=1);

namespace Inertify\Table\Support;

class TableState
{
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly ?string $sort,
        public readonly ?string $direction,
        public readonly array $filters
    ) {}

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'perPage' => $this->perPage,
            'sort' => $this->sort,
            'direction' => $this->direction,
            'filters' => $this->filters,
        ];
    }
}
