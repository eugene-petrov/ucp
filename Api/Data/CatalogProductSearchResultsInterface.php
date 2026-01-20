<?php
/**
 * UCP Catalog Product Search Results Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface CatalogProductSearchResultsInterface
{
    /**
     * Get products
     *
     * @return \Aeqet\Ucp\Api\Data\CatalogProductInterface[]
     */
    public function getProducts(): array;

    /**
     * Set products
     *
     * @param \Aeqet\Ucp\Api\Data\CatalogProductInterface[] $products
     * @return $this
     */
    public function setProducts(array $products): self;

    /**
     * Get total count
     *
     * @return int
     */
    public function getTotalCount(): int;

    /**
     * Set total count
     *
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount(int $totalCount): self;

    /**
     * Get page size
     *
     * @return int
     */
    public function getPageSize(): int;

    /**
     * Set page size
     *
     * @param int $pageSize
     * @return $this
     */
    public function setPageSize(int $pageSize): self;

    /**
     * Get current page
     *
     * @return int
     */
    public function getCurrentPage(): int;

    /**
     * Set current page
     *
     * @param int $currentPage
     * @return $this
     */
    public function setCurrentPage(int $currentPage): self;

    /**
     * Get total pages
     *
     * @return int
     */
    public function getTotalPages(): int;

    /**
     * Set total pages
     *
     * @param int $totalPages
     * @return $this
     */
    public function setTotalPages(int $totalPages): self;
}
