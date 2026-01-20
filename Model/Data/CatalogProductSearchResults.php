<?php
/**
 * UCP Catalog Product Search Results Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface;
use Magento\Framework\DataObject;

class CatalogProductSearchResults extends DataObject implements CatalogProductSearchResultsInterface
{
    /**
     * @inheritDoc
     */
    public function getProducts(): array
    {
        return $this->getData('products') ?? [];
    }

    /**
     * @inheritDoc
     */
    public function setProducts(array $products): CatalogProductSearchResultsInterface
    {
        return $this->setData('products', $products);
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount(): int
    {
        return (int) $this->getData('total_count');
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount(int $totalCount): CatalogProductSearchResultsInterface
    {
        return $this->setData('total_count', $totalCount);
    }

    /**
     * @inheritDoc
     */
    public function getPageSize(): int
    {
        return (int) $this->getData('page_size');
    }

    /**
     * @inheritDoc
     */
    public function setPageSize(int $pageSize): CatalogProductSearchResultsInterface
    {
        return $this->setData('page_size', $pageSize);
    }

    /**
     * @inheritDoc
     */
    public function getCurrentPage(): int
    {
        return (int) $this->getData('current_page');
    }

    /**
     * @inheritDoc
     */
    public function setCurrentPage(int $currentPage): CatalogProductSearchResultsInterface
    {
        return $this->setData('current_page', $currentPage);
    }

    /**
     * @inheritDoc
     */
    public function getTotalPages(): int
    {
        return (int) $this->getData('total_pages');
    }

    /**
     * @inheritDoc
     */
    public function setTotalPages(int $totalPages): CatalogProductSearchResultsInterface
    {
        return $this->setData('total_pages', $totalPages);
    }
}
