<?php
/**
 * UCP Catalog Category Management Implementation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\CatalogCategoryManagementInterface;
use Aeqet\Ucp\Api\Data\CatalogCategoryInterface;
use Aeqet\Ucp\Api\Data\CatalogCategoryInterfaceFactory;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterface;
use Aeqet\Ucp\Api\Data\CatalogProductSearchResultsInterfaceFactory;
use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CatalogCategoryManagement implements CatalogCategoryManagementInterface
{
    private const DEFAULT_ROOT_CATEGORY_ID = 2;

    /**
     * Constructor
     *
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CatalogCategoryInterfaceFactory $catalogCategoryFactory
     * @param CatalogProductSearchResultsInterfaceFactory $searchResultsFactory
     * @param ProductToUcpConverter $productConverter
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly CatalogCategoryInterfaceFactory $catalogCategoryFactory,
        private readonly CatalogProductSearchResultsInterfaceFactory $searchResultsFactory,
        private readonly ProductToUcpConverter $productConverter,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getTree(?string $rootId = null, int $depth = 3): array
    {
        $rootCategoryId = $this->extractCategoryId($rootId);

        if ($rootCategoryId === null) {
            try {
                $rootCategoryId = (int) $this->storeManager->getStore()->getRootCategoryId();
            } catch (Exception $e) {
                $this->logger->debug('Unable to get store root category ID, using default', [
                    'exception' => $e->getMessage()
                ]);
                $rootCategoryId = self::DEFAULT_ROOT_CATEGORY_ID;
            }
        }

        return $this->buildCategoryTree($rootCategoryId, $depth, 0);
    }

    /**
     * @inheritDoc
     */
    public function get(string $categoryId): CatalogCategoryInterface
    {
        $id = $this->extractCategoryId($categoryId);
        if ($id === null) {
            throw new NoSuchEntityException(__('Category ID is required.'));
        }

        try {
            $category = $this->categoryRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Category not found', [
                'category_id' => $categoryId,
                'numeric_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Category with ID "%1" not found.', $categoryId));
        }

        return $this->convertCategory($category, true);
    }

    /**
     * @inheritDoc
     */
    public function getProducts(
        string $categoryId,
        int $limit = 20,
        int $offset = 0
    ): CatalogProductSearchResultsInterface {
        $id = $this->extractCategoryId($categoryId);

        if ($id === null) {
            throw new NoSuchEntityException(__('Category ID is required.'));
        }

        try {
            $this->categoryRepository->get($id);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Category not found when fetching products', [
                'category_id' => $categoryId,
                'numeric_id' => $id,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Category with ID "%1" not found.', $categoryId));
        }

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(
            ['name', 'description', 'short_description', 'price', 'image', 'thumbnail', 'url_key']
        );
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['in' => [
            Visibility::VISIBILITY_IN_CATALOG,
            Visibility::VISIBILITY_BOTH
        ]]);
        $collection->addCategoriesFilter(['in' => [$id]]);
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
     * Build category tree recursively
     *
     * @param int $parentId
     * @param int $maxDepth
     * @param int $currentDepth
     * @return CatalogCategoryInterface[]
     */
    private function buildCategoryTree(int $parentId, int $maxDepth, int $currentDepth): array
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'description', 'image', 'url_path', 'url_key']);
        $collection->addAttributeToFilter('parent_id', $parentId);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->setOrder('position', 'ASC');

        $categories = [];
        foreach ($collection as $category) {
            $ucpCategory = $this->convertCategory($category, true);

            // Build children recursively
            if ($currentDepth + 1 < $maxDepth) {
                $children = $this->buildCategoryTree((int) $category->getId(), $maxDepth, $currentDepth + 1);
                if (!empty($children)) {
                    $ucpCategory->setChildren($children);
                }
            }

            $categories[] = $ucpCategory;
        }

        return $categories;
    }

    /**
     * Convert Magento category to UCP category
     *
     * @param CategoryInterface $category
     * @param bool $includeProductCount
     * @return CatalogCategoryInterface
     */
    private function convertCategory($category, bool $includeProductCount = true): CatalogCategoryInterface
    {
        $ucpCategory = $this->catalogCategoryFactory->create();

        $ucpCategory->setId('category_' . $category->getId());
        $ucpCategory->setName($category->getName() ?? '');
        $ucpCategory->setDescription($this->getCleanDescription($category));
        $ucpCategory->setUrl($this->getCategoryUrl($category));
        $ucpCategory->setLevel((int) $category->getLevel());

        // Set parent ID
        $parentId = (int) $category->getParentId();
        if ($parentId > 1) { // 1 is the root catalog category
            $ucpCategory->setParentId('category_' . $parentId);
        }

        // Set image URL
        $image = $category->getImage();
        if ($image) {
            try {
                $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                $ucpCategory->setImageUrl($mediaUrl . 'catalog/category/' . $image);
            } catch (Exception $e) {
                $this->logger->debug('Unable to get category image URL', [
                    'category_id' => $category->getId(),
                    'exception' => $e->getMessage()
                ]);
            }
        }

        if ($includeProductCount) {
            $ucpCategory->setProductCount($this->getProductCount($category));
        }

        return $ucpCategory;
    }

    /**
     * Get clean category description
     *
     * @param CategoryInterface $category
     * @return string|null
     */
    private function getCleanDescription($category): ?string
    {
        $description = $category->getDescription();
        if (!$description) {
            return null;
        }

        $description = strip_tags($description);
        $description = trim(preg_replace('/\s+/', ' ', $description));

        if (strlen($description) > 500) {
            $description = substr($description, 0, 497) . '...';
        }

        return $description;
    }

    /**
     * Get category URL
     *
     * @param CategoryInterface $category
     * @return string
     */
    private function getCategoryUrl($category): string
    {
        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $urlPath = $category->getUrlPath() ?: $category->getUrlKey();
            if ($urlPath) {
                return $baseUrl . $urlPath . '.html';
            }
        } catch (Exception $e) {
            $this->logger->debug('Unable to get category URL', [
                'category_id' => $category->getId(),
                'exception' => $e->getMessage()
            ]);
        }

        return '';
    }

    /**
     * Get product count in category
     *
     * @param CategoryInterface $category
     * @return int
     */
    private function getProductCount($category): int
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addCategoriesFilter(['in' => [(int) $category->getId()]]);
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addAttributeToFilter('visibility', ['in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_BOTH
            ]]);
            return $collection->getSize();
        } catch (Exception $e) {
            $this->logger->debug('Unable to get category product count', [
                'category_id' => $category->getId(),
                'exception' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Extract numeric category ID from UCP format
     *
     * @param string|null $categoryId
     * @return int|null
     */
    private function extractCategoryId(?string $categoryId): ?int
    {
        if ($categoryId === null || $categoryId === '') {
            return null;
        }

        $id = str_replace('category_', '', $categoryId);

        if (!is_numeric($id)) {
            return null;
        }

        return (int) $id;
    }
}
