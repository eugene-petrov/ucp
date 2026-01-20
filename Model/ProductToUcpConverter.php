<?php
/**
 * Product to UCP Converter
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\Data\CatalogImageInterface;
use Aeqet\Ucp\Api\Data\CatalogImageInterfaceFactory;
use Aeqet\Ucp\Api\Data\CatalogProductInterface;
use Aeqet\Ucp\Api\Data\CatalogProductInterfaceFactory;
use Aeqet\Ucp\Api\Data\VariantInterface;
use Aeqet\Ucp\Api\Data\VariantInterfaceFactory;
use Aeqet\Ucp\Api\Data\VariantAttributeInterface;
use Aeqet\Ucp\Api\Data\VariantAttributeInterfaceFactory;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductToUcpConverter
{
    /**
     * Constructor
     *
     * @param CatalogProductInterfaceFactory $catalogProductFactory
     * @param CatalogImageInterfaceFactory $catalogImageFactory
     * @param VariantInterfaceFactory $variantFactory
     * @param VariantAttributeInterfaceFactory $variantAttributeFactory
     * @param StoreManagerInterface $storeManager
     * @param ImageHelper $imageHelper
     * @param StockRegistryInterface $stockRegistry
     * @param Configurable $configurableType
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CatalogProductInterfaceFactory $catalogProductFactory,
        private readonly CatalogImageInterfaceFactory $catalogImageFactory,
        private readonly VariantInterfaceFactory $variantFactory,
        private readonly VariantAttributeInterfaceFactory $variantAttributeFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ImageHelper $imageHelper,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly Configurable $configurableType,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Convert Magento Product to UCP Catalog Product
     *
     * @param ProductInterface $product
     * @param bool $includeVariants Whether to include variants for configurable products
     * @return CatalogProductInterface
     */
    public function convert(ProductInterface $product, bool $includeVariants = true): CatalogProductInterface
    {
        $ucpProduct = $this->catalogProductFactory->create();

        $ucpProduct->setId('product_' . $product->getId());
        $ucpProduct->setSku($product->getSku());
        $ucpProduct->setTitle($product->getName() ?? '');
        $ucpProduct->setDescription($this->getCleanDescription($product));
        $ucpProduct->setPrice($this->toCents((float) $product->getPrice()));
        $ucpProduct->setCurrency($this->getCurrency());
        $ucpProduct->setImages($this->getImages($product));
        $ucpProduct->setUrl($this->getProductUrl($product));
        $ucpProduct->setInStock($this->isInStock($product));
        $ucpProduct->setAttributes($this->getAttributes($product));

        if ($includeVariants && $product->getTypeId() === Configurable::TYPE_CODE) {
            $ucpProduct->setVariants($this->getVariants($product));
        }

        return $ucpProduct;
    }

    /**
     * Get variants for configurable product
     *
     * @param ProductInterface $product
     * @return VariantInterface[]
     */
    private function getVariants(ProductInterface $product): array
    {
        $variants = [];

        try {
            $configurableAttributes = $this->configurableType->getConfigurableAttributesAsArray($product);
            $attributeCodes = [];
            foreach ($configurableAttributes as $attribute) {
                $attributeCodes[] = $attribute['attribute_code'];
            }

            $childProducts = $this->configurableType->getUsedProducts($product);

            foreach ($childProducts as $child) {
                /** @var VariantInterface $variant */
                $variant = $this->variantFactory->create();
                $variant->setId('product_' . $child->getId());
                $variant->setSku($child->getSku());
                $variant->setPrice($this->toCents((float) $child->getPrice()));
                $variant->setInStock($this->isInStock($child));

                $attributes = [];
                foreach ($attributeCodes as $code) {
                    $value = $this->getAttributeValue($child, $code);
                    if ($value !== null) {
                        /** @var VariantAttributeInterface $attrObj */
                        $attrObj = $this->variantAttributeFactory->create();
                        $attrObj->setCode($code);
                        $attrObj->setValue($value);
                        $attributes[] = $attrObj;
                    }
                }
                $variant->setAttributes($attributes);

                $variants[] = $variant;
            }
        } catch (Exception $e) {
            $this->logger->debug('Unable to get configurable product variants', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);
        }

        return $variants;
    }

    /**
     * Get clean description (strip HTML, truncate)
     *
     * @param ProductInterface $product
     * @return string|null
     */
    private function getCleanDescription(ProductInterface $product): ?string
    {
        $description = $product->getDescription();
        if (!$description) {
            $description = $product->getShortDescription();
        }

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
     * Get store currency
     *
     * @return string
     */
    private function getCurrency(): string
    {
        try {
            return $this->storeManager->getStore()->getCurrentCurrencyCode() ?? 'USD';
        } catch (Exception $e) {
            $this->logger->debug('Unable to get store currency, using USD', [
                'exception' => $e->getMessage()
            ]);
            return 'USD';
        }
    }

    /**
     * Get product images
     *
     * @param ProductInterface $product
     * @return CatalogImageInterface[]
     */
    private function getImages(ProductInterface $product): array
    {
        $images = [];
        $mediaGallery = $product->getMediaGalleryEntries();

        if (!$mediaGallery) {
            $mainImage = $this->getMainImage($product);
            if ($mainImage) {
                $images[] = $mainImage;
            }
            return $images;
        }

        $position = 1;
        foreach ($mediaGallery as $entry) {
            if ($entry->isDisabled()) {
                continue;
            }

            $image = $this->catalogImageFactory->create();
            $image->setId('img_' . $entry->getId());

            try {
                $imageUrl = $this->imageHelper->init($product, 'product_base_image')
                    ->setImageFile($entry->getFile())
                    ->getUrl();
                $image->setUrl($imageUrl);

                $thumbnailUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                    ->setImageFile($entry->getFile())
                    ->getUrl();
                $image->setThumbnailUrl($thumbnailUrl);
            } catch (Exception $e) {
                $this->logger->debug('Unable to get product gallery image', [
                    'product_id' => $product->getId(),
                    'image_id' => $entry->getId(),
                    'exception' => $e->getMessage()
                ]);
                continue;
            }

            $image->setAltText($entry->getLabel() ?: $product->getName());
            $image->setPosition((int) $entry->getPosition() ?: $position);
            $images[] = $image;
            $position++;
        }

        return $images;
    }

    /**
     * Get main product image as fallback
     *
     * @param ProductInterface $product
     * @return CatalogImageInterface|null
     */
    private function getMainImage(ProductInterface $product): ?CatalogImageInterface
    {
        try {
            $imageUrl = $this->imageHelper->init($product, 'product_base_image')
                ->setImageFile($product->getImage())
                ->getUrl();

            $thumbnailUrl = $this->imageHelper->init($product, 'product_thumbnail_image')
                ->setImageFile($product->getThumbnail())
                ->getUrl();

            $image = $this->catalogImageFactory->create();
            $image->setId('img_main');
            $image->setUrl($imageUrl);
            $image->setThumbnailUrl($thumbnailUrl);
            $image->setAltText($product->getName());
            $image->setPosition(1);

            return $image;
        } catch (Exception $e) {
            $this->logger->debug('Unable to get main product image', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get product URL
     *
     * @param ProductInterface $product
     * @return string
     */
    private function getProductUrl(ProductInterface $product): string
    {
        if (method_exists($product, 'getProductUrl')) {
            return $product->getProductUrl();
        }

        try {
            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $urlKey = $product->getUrlKey();
            if ($urlKey) {
                return $baseUrl . $urlKey . '.html';
            }
        } catch (Exception $e) {
            $this->logger->debug('Unable to get product URL', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);
        }

        return '';
    }

    /**
     * Check if product is in stock
     *
     * @param ProductInterface $product
     * @return bool
     */
    private function isInStock(ProductInterface $product): bool
    {
        try {
            $stockItem = $this->stockRegistry->getStockItem($product->getId());
            return $stockItem->getIsInStock();
        } catch (Exception $e) {
            $this->logger->debug('Unable to get product stock status', [
                'product_id' => $product->getId(),
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get product attributes as key-value pairs
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getAttributes(ProductInterface $product): array
    {
        $attributes = [];

        $attributeCodes = ['color', 'size', 'material', 'brand', 'manufacturer'];

        foreach ($attributeCodes as $code) {
            $value = $this->getAttributeValue($product, $code);
            if ($value !== null) {
                $attributes[$code] = $value;
            }
        }

        return $attributes;
    }

    /**
     * Get attribute value (resolving option labels for select attributes)
     *
     * @param ProductInterface $product
     * @param string $attributeCode
     * @return string|null
     */
    private function getAttributeValue(ProductInterface $product, string $attributeCode): ?string
    {
        $value = $product->getData($attributeCode);
        if ($value === null || $value === '') {
            return null;
        }

        if (method_exists($product, 'getResource')) {
            try {
                $resource = $product->getResource();
                $attribute = $resource->getAttribute($attributeCode);
                if ($attribute && $attribute->usesSource()) {
                    $optionText = $attribute->getSource()->getOptionText($value);
                    if (is_array($optionText)) {
                        return implode(', ', $optionText);
                    }
                    return $optionText ?: (string) $value;
                }
            } catch (Exception $e) {
                $this->logger->debug('Unable to get attribute option text', [
                    'product_id' => $product->getId(),
                    'attribute_code' => $attributeCode,
                    'exception' => $e->getMessage()
                ]);
            }
        }

        return (string) $value;
    }

    /**
     * Convert dollars to cents
     *
     * @param float $amount
     * @return int
     */
    private function toCents(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
