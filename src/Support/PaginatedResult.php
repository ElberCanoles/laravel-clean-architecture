<?php

declare(strict_types=1);

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
        if ($this->total < 0) {
            throw new \InvalidArgumentException("Total must be zero or positive, got {$this->total}.");
        }

        if ($this->page < 1 || $this->perPage < 1) {
            throw new \InvalidArgumentException(
                "Page and perPage must be positive, got page {$this->page} and perPage {$this->perPage}."
            );
        }
    }

    public function lastPage(): int
    {
        return max((int) ceil($this->total / $this->perPage), 1);
    }

    public function hasMorePages(): bool
    {
        return $this->page < $this->lastPage();
    }

    /** @return array{total: int, page: int, per_page: int, last_page: int} */
    public function meta(): array
    {
        return [
            'total' => $this->total,
            'page' => $this->page,
            'per_page' => $this->perPage,
            'last_page' => $this->lastPage(),
        ];
    }
}
