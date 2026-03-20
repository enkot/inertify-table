<?php

declare(strict_types=1);

namespace Taras\InertiaHeadlessTable;

use Illuminate\Support\Str;

class Column
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $sortable = false,
        public bool $filterable = false,
        public bool $hidden = false,
        public array $meta = []
    ) {}

    public static function make(string $key, ?string $label = null): self
    {
        return new self($key, $label ?? Str::headline(str_replace('.', ' ', $key)));
    }

    public function sortable(bool $sortable = true): self
    {
        $this->sortable = $sortable;

        return $this;
    }

    public function filterable(bool $filterable = true): self
    {
        $this->filterable = $filterable;

        return $this;
    }

    public function hidden(bool $hidden = true): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function type(string $type): self
    {
        $type = trim($type);

        if ($type !== '') {
            $this->meta['type'] = $type;
        }

        return $this;
    }

    public function withMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'sortable' => $this->sortable,
            'filterable' => $this->filterable,
            'hidden' => $this->hidden,
            'meta' => $this->meta,
        ];
    }
}
