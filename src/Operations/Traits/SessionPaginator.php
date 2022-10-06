<?php

namespace Flamix\CommerceML\Operations\Traits;

trait SessionPaginator
{
    private int $elementsCount = 0;

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
    }

    // Setters
    public function setElementsCount(int $elementsCount): object
    {
        if ($elementsCount === 0)
            throw new \Exception('Products not found!');

        $this->elementsCount = $elementsCount;
        return $this;
    }

    public function setPage(int $page): int
    {
        $this->startSession();
        $_SESSION['flamix_page'] = $page;
        return $this->currentPage();
    }

    // Getters
    public function perPage(): int
    {
        return exchange_config('exchange.items_per_page', 30);
    }

    public function getElementsCount(): int
    {
        return $this->elementsCount;
    }

    public function calculatePage(): int
    {
        return ceil($this->getElementsCount() / $this->perPage());
    }

    public function currentPage(): int
    {
        $this->startSession();
        return (int)($_SESSION['flamix_page'] ?? 0);
    }

    public function currentElement(): int
    {
        return $this->getElementsCount() * $this->currentPage();
    }

    public function getNextPage(): int
    {
        $current_page = $this->currentPage();
        return ++$current_page;
    }

    public function setNextPage(): int
    {
        return $this->setPage($this->getNextPage());
    }

    // Checkers

    public function isFinish(): bool
    {
        return $this->currentPage() > $this->calculatePage();
    }

    public function isStart(): bool
    {
        return $this->currentPage() === 0;
    }
}
