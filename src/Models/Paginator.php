<?php

namespace App\Models;

/**
 * Paginator
 *
 * Fournit la logique de pagination simple : page courante, total de pages,
 * offset pour les requêtes SQL et validation de page via query param `page`.
 */
class Paginator
{
    private int $totalItems;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;

    /**
     * @param int $totalItems Nombre total d'éléments à paginer
     * @param int $perPage Nombre d'éléments par page (défaut 10)
     */
    public function __construct(int $totalItems, int $perPage = 10)
    {
        $this->totalItems = $totalItems;
        $this->perPage = $perPage;
        $this->totalPages = max(1, (int) ceil($totalItems / $perPage));
        $this->currentPage = $this->readCurrentPage();
    }

    /**
     * Renvoie la page actuellement demandée/validée.
     *
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Renvoie le nombre d'éléments par page.
     *
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Calcule l'offset SQL pour la page courante.
     *
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * Nombre total de pages calculé.
     *
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Lit la page courante via $_GET['page'], assure qu'elle est numérique
     * et dans l'intervalle [1, totalPages].
     *
     * @return int page courante validée
     */
    private function readCurrentPage(): int
    {
        if (!isset($_GET['page']) || !is_numeric($_GET['page'])) {
            return 1;
        }

        $page = (int) $_GET['page'];
        return max(1, min($page, $this->totalPages));
    }
}