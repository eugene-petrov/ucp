<?php
/**
 * UCP Catalog Product Management Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

use Aeqet\Ucp\Api\Data\CatalogProductInterface;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Interface for UCP catalog product operations
 *
 * @api
 */
interface CatalogProductManagementInterface
{
    /**
     * Search products
     *
     * @param string|null $query Search query string
     * @param int|null $categoryId Filter by category ID
     * @param int $limit Number of results per page
     * @param int $offset Results offset for pagination
     * @return \Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface
     */
    public function search(
        ?string $query = null,
        ?int $categoryId = null,
        int $limit = 20,
        int $offset = 0
    ): CatalogProductSearchResultsInterface;

    /**
     * Get product by ID
     *
     * @param string $productId Product ID (can include "product_" prefix)
     * @return \Aeqet\Ucp\Api\Data\CatalogProductInterface
     * @throws NoSuchEntityException
     */
    public function get(string $productId): CatalogProductInterface;

    /**
     * Get product by SKU
     *
     * @param string $sku Product SKU
     * @return \Aeqet\Ucp\Api\Data\CatalogProductInterface
     * @throws NoSuchEntityException
     */
    public function getBySku(string $sku): CatalogProductInterface;
}
