<?php

namespace App\Models;

class Paginator
{
    private int $totalItems;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;

    public function __construct(int $totalItems, int $perPage = 10)
    {
        $this->totalItems = $totalItems;
        $this->perPage = $perPage;
        $this->totalPages = max(1, (int) ceil($totalItems / $perPage));
        $this->currentPage = $this->readCurrentPage();
    }

    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    private function readCurrentPage(): int
    {
        if (!isset($_GET['page']) || !is_numeric($_GET['page'])) {
            return 1;
        }

        $page = (int) $_GET['page'];
        return max(1, min($page, $this->totalPages));
    }
}