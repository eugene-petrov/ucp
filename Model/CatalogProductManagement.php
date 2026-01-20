<?php
/**
 * UCP Catalog Product Management Implementation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\CatalogProductManagementInterface;
use Aeqet\Ucp\Api\Data\CatalogProductInterface;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CatalogProductManagement implements CatalogProductManagementInterface
{
    /**
     * Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ProductToUcpConverter $productConverter
     * @param CatalogProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly ProductToUcpConverter $productConverter,
        private readonly CatalogProductSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function search(
        ?string $query = null,
        ?int $categoryId = null,
        int $limit = 20,
        int $offset = 0
    ): CatalogProductSearchResultsInterface {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(
            ['name', 'description', 'short_description', 'price', 'image', 'thumbnail', 'url_key']
        );
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['in' => [
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_BOTH
        ]]);

        if ($query) {
            $collection->addAttributeToFilter(
                [
                    ['attribute' => 'name', 'like' => '%' . $query . '%'],
                    ['attribute' => 'sku', 'like' => '%' . $query . '%'],
                    ['attribute' => 'description', 'like' => '%' . $query . '%']
                ]
            );
        }

        if ($categoryId) {
            $collection->addCategoriesFilter(['in' => [$categoryId]]);
        }

        $totalCount = $collection->getSize();

        $currentPage = (int) floor($offset / max($limit, 1)) + 1;
        $collection->setPageSize($limit);
        $collection->setCurPage($currentPage);

        $products = [];
        foreach ($collection as $product) {
            $products[] = $this->productConverter->convert($product);
        }

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setProducts($products);
        $searchResults->setTotalCount($totalCount);
        $searchResults->setPageSize($limit);
        $searchResults->setCurrentPage($currentPage);
        $searchResults->setTotalPages((int) ceil($totalCount / max($limit, 1)));

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function get(string $productId): CatalogProductInterface
    {
        $id = str_replace('product_', '', $productId);

        try {
            $product = $this->productRepository->getById((int) $id);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Product not found by ID', [
                'product_id' => $productId,
                'numeric_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Product with ID "%1" not found.', $productId));
        }

        return $this->productConverter->convert($product);
    }

    /**
     * @inheritDoc
     */
    public function getBySku(string $sku): CatalogProductInterface
    {
        try {
            $product = $this->productRepository->get($sku);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Product not found by SKU', [
                'sku' => $sku,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Product with SKU "%1" not found.', $sku));
        }

        return $this->productConverter->convert($product);
    }
}
