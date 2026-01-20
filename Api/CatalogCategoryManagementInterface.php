<?php
/**
 * UCP Catalog Category Management Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

use Aeqet\Ucp\Api\Data\CatalogCategoryInterface;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface;

interface CatalogCategoryManagementInterface
{
    /**
     * Get category tree
     *
     * @param string|null $rootId Start from category (null = root). Format: "category_123" or "123"
     * @param int $depth Max depth (default 3)
     * @return \Aeqet\Ucp\Api\Data\CatalogCategoryInterface[]
     */
    public function getTree(?string $rootId = null, int $depth = 3): array;

    /**
     * Get category by ID
     *
     * @param string $categoryId Category ID (with or without "category_" prefix)
     * @return \Aeqet\Ucp\Api\Data\CatalogCategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $categoryId): CatalogCategoryInterface;

    /**
     * Get products in category
     *
     * @param string $categoryId Category ID (with or without "category_" prefix)
     * @param int $limit Results per page (default 20)
     * @param int $offset Skip results (default 0)
     * @return \Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProducts(
        string $categoryId,
        int $limit = 20,
        int $offset = 0
    ): CatalogProductSearchResultsInterface;
}
