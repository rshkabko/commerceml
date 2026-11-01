<?php

namespace Flamix\CommerceML\Operations\Traits;

/** Export paging kept in $_SESSION['flamix_page'], so it survives between exchange requests. */
trait SessionPaginator
{
    private int $elementsCount = 0;

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
    }

    /** @throws \Exception on 0: an empty shop fails the export instead of succeeding with nothing. */
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

    public function perPage(): int
    {
        return commerceml_config('items_per_page', 30);
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

    /** Elements exported before the current page, capped at the total. */
    public function currentElement(): int
    {
        $current_max = $this->perPage() * $this->currentPage();
        return ($current_max > $this->getElementsCount()) ? $this->getElementsCount() : $current_max;
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

    public function isFinish(): bool
    {
        return $this->currentPage() > $this->calculatePage();
    }

    public function isStart(): bool
    {
        return $this->currentPage() === 0;
    }
}
