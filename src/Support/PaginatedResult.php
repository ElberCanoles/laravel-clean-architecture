<?php

namespace CleanArchitecture\Support;

/** @template T */
readonly class PaginatedResult
{
    /**
     * @param  T[]  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {
    }

    /** @return array{total: int, page: int, per_page: int} */
    public function meta(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }
}
