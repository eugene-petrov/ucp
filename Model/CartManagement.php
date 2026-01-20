<?php
/**
 * UCP Cart Management Implementation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\CartManagementInterface;
use Aeqet\Ucp\Api\Data\CartInterface;
use Aeqet\Ucp\Api\Data\CartInterfaceFactory;
use Aeqet\Ucp\Api\Data\CartItemInterface;
use Aeqet\Ucp\Api\Data\CartItemInterfaceFactory;
use Aeqet\Ucp\Api\Data\CartItemOptionInterface;
use Aeqet\Ucp\Api\Data\TotalInterface;
use Aeqet\Ucp\Api\Data\TotalInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface as MagentoCartInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Quote\Model\ResourceModel\Quote\QuoteIdMask as QuoteIdMaskResource;
use Psr\Log\LoggerInterface;

class CartManagement implements CartManagementInterface
{
    /**
     * Constructor
     *
     * @param GuestCartManagementInterface $guestCartManagement
     * @param CartRepositoryInterface $cartRepository
     * @param CartInterfaceFactory $ucpCartFactory
     * @param CartItemInterfaceFactory $ucpCartItemFactory
     * @param TotalInterfaceFactory $totalFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ProductToUcpConverter $productConverter
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param QuoteIdMaskResource $quoteIdMaskResource
     * @param Configurable $configurableType
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly GuestCartManagementInterface $guestCartManagement,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartInterfaceFactory $ucpCartFactory,
        private readonly CartItemInterfaceFactory $ucpCartItemFactory,
        private readonly TotalInterfaceFactory $totalFactory,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductToUcpConverter $productConverter,
        private readonly QuoteIdMaskFactory $quoteIdMaskFactory,
        private readonly QuoteIdMaskResource $quoteIdMaskResource,
        private readonly Configurable $configurableType,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(): CartInterface
    {
        $maskedId = $this->guestCartManagement->createEmptyCart();
        return $this->get('cart_' . $maskedId);
    }

    /**
     * @inheritDoc
     */
    public function get(string $cartId): CartInterface
    {
        $maskedId = $this->extractCartId($cartId);
        $quote = $this->getQuoteByMaskedId($maskedId);

        return $this->convertQuoteToCart($quote, $maskedId);
    }

    /**
     * @inheritDoc
     */
    public function addItem(string $cartId, string $productId, int $quantity, ?array $options = null): CartInterface
    {
        $maskedId = $this->extractCartId($cartId);
        $quote = $this->getQuoteByMaskedId($maskedId);
        $numericProductId = $this->extractProductId($productId);
        try {
            $product = $this->productRepository->getById($numericProductId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Product not found when adding to cart', [
                'product_id' => $productId,
                'numeric_product_id' => $numericProductId,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Product with ID "%1" not found.', $productId));
        }

        $requestData = ['qty' => $quantity];
        if ($product->getTypeId() === Configurable::TYPE_CODE) {
            if ($options !== null && !empty($options)) {
                // Convert attribute labels to super_attribute format
                $superAttributes = $this->convertOptionsToSuperAttributes($product, $options);
                if (!empty($superAttributes)) {
                    $requestData['super_attribute'] = $superAttributes;
                } else {
                    throw new LocalizedException(__(
                        'Please specify product options. Available options: %1',
                        implode(', ', array_keys($this->getAvailableOptions($product)))
                    ));
                }
            } else {
                throw new LocalizedException(__(
                    'This is a configurable product. Please specify options or use a variant product ID. ' .
                    'Available options: %1',
                    implode(', ', array_keys($this->getAvailableOptions($product)))
                ));
            }
        }

        $request = new DataObject($requestData);
        $result = $quote->addProduct($product, $request);

        if (is_string($result)) {
            throw new LocalizedException(__($result));
        }

        $this->cartRepository->save($quote);

        $quote = $this->cartRepository->get($quote->getId());

        return $this->convertQuoteToCart($quote, $maskedId);
    }

    /**
     * Convert option labels to Magento super_attribute format
     *
     * @param ProductInterface $product
     * @param CartItemOptionInterface[] $options Options as CartItemOptionInterface array
     * @return array Super attributes as attribute_id => option_id pairs
     */
    private function convertOptionsToSuperAttributes($product, array $options): array
    {
        $superAttributes = [];
        $configurableAttributes = $this->configurableType->getConfigurableAttributesAsArray($product);

        $optionsMap = [];
        foreach ($options as $option) {
            if ($option instanceof CartItemOptionInterface) {
                $optionsMap[$option->getCode()] = $option->getValue();
            }
        }

        foreach ($configurableAttributes as $attribute) {
            $attributeCode = $attribute['attribute_code'];
            $attributeId = $attribute['attribute_id'];
            if (isset($optionsMap[$attributeCode])) {
                $requestedValue = $optionsMap[$attributeCode];
                foreach ($attribute['options'] as $option) {
                    if (strcasecmp($option['label'], $requestedValue) === 0 ||
                        $option['value'] == $requestedValue) {
                        $superAttributes[$attributeId] = $option['value'];
                        break;
                    }
                }
            }
        }

        return $superAttributes;
    }

    /**
     * Get available options for a configurable product
     *
     * @param ProductInterface $product
     * @return array Options as attribute_code => [available values]
     */
    private function getAvailableOptions($product): array
    {
        $availableOptions = [];
        $configurableAttributes = $this->configurableType->getConfigurableAttributesAsArray($product);

        foreach ($configurableAttributes as $attribute) {
            $attributeCode = $attribute['attribute_code'];
            $values = [];
            foreach ($attribute['options'] as $option) {
                if (!empty($option['label'])) {
                    $values[] = $option['label'];
                }
            }
            $availableOptions[$attributeCode] = $values;
        }

        return $availableOptions;
    }

    /**
     * @inheritDoc
     */
    public function updateItem(string $cartId, string $itemId, int $quantity): CartInterface
    {
        $maskedId = $this->extractCartId($cartId);
        $quote = $this->getQuoteByMaskedId($maskedId);
        $numericItemId = $this->extractItemId($itemId);
        $item = $quote->getItemById($numericItemId);
        if (!$item) {
            throw new NoSuchEntityException(__('Cart item with ID "%1" not found.', $itemId));
        }

        if ($quantity <= 0) {
            $quote->removeItem($numericItemId);
        } else {
            $item->setQty($quantity);
        }

        $this->cartRepository->save($quote);

        return $this->convertQuoteToCart($quote, $maskedId);
    }

    /**
     * @inheritDoc
     */
    public function removeItem(string $cartId, string $itemId): CartInterface
    {
        $maskedId = $this->extractCartId($cartId);
        $quote = $this->getQuoteByMaskedId($maskedId);
        $numericItemId = $this->extractItemId($itemId);
        $item = $quote->getItemById($numericItemId);
        if (!$item) {
            throw new NoSuchEntityException(__('Cart item with ID "%1" not found.', $itemId));
        }

        $quote->removeItem($numericItemId);
        $this->cartRepository->save($quote);

        return $this->convertQuoteToCart($quote, $maskedId);
    }

    /**
     * Get quote by masked ID
     *
     * @param string $maskedId
     * @return MagentoCartInterface
     * @throws NoSuchEntityException
     */
    private function getQuoteByMaskedId(string $maskedId): MagentoCartInterface
    {
        $quoteIdMask = $this->quoteIdMaskFactory->create();
        $this->quoteIdMaskResource->load($quoteIdMask, $maskedId, 'masked_id');

        if (!$quoteIdMask->getQuoteId()) {
            throw new NoSuchEntityException(__('Cart with ID "%1" not found.', 'cart_' . $maskedId));
        }

        return $this->cartRepository->get($quoteIdMask->getQuoteId());
    }

    /**
     * Convert Magento Quote to UCP Cart
     *
     * @param MagentoCartInterface $quote
     * @param string $maskedId
     * @return CartInterface
     */
    private function convertQuoteToCart(MagentoCartInterface $quote, string $maskedId): CartInterface
    {
        $cart = $this->ucpCartFactory->create();

        $cart->setId('cart_' . $maskedId);
        $cart->setCurrency($quote->getQuoteCurrencyCode() ?: 'USD');
        $cart->setItems($this->convertItems($quote));
        $cart->setTotals($this->convertTotals($quote));
        $cart->setItemCount((int) $quote->getItemsQty());

        return $cart;
    }

    /**
     * Convert quote items to UCP cart items
     *
     * @param MagentoCartInterface $quote
     * @return CartItemInterface[]
     */
    private function convertItems(MagentoCartInterface $quote): array
    {
        $items = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            $ucpItem = $this->ucpCartItemFactory->create();
            $ucpItem->setId('item_' . $item->getId());
            $ucpItem->setQuantity((int) $item->getQty());
            $ucpItem->setPrice($this->toCents((float) $item->getPrice()));
            $ucpItem->setSubtotal($this->toCents((float) $item->getRowTotal()));
            $product = $item->getProduct();
            $ucpProduct = $this->productConverter->convert($product);
            $ucpItem->setProduct($ucpProduct);

            $items[] = $ucpItem;
        }

        return $items;
    }

    /**
     * Convert quote totals to UCP totals
     *
     * @param MagentoCartInterface $quote
     * @return TotalInterface[]
     */
    private function convertTotals(MagentoCartInterface $quote): array
    {
        $totals = [];

        $subtotal = $this->totalFactory->create();
        $subtotal->setType(TotalInterface::TYPE_SUBTOTAL);
        $subtotal->setAmount($this->toCents((float) $quote->getSubtotal()));
        $subtotal->setDisplayText('Subtotal');
        $totals[] = $subtotal;

        $discountAmount = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $discountAmount += (float) $item->getDiscountAmount();
        }
        if ($discountAmount > 0) {
            $discount = $this->totalFactory->create();
            $discount->setType(TotalInterface::TYPE_DISCOUNT);
            $discount->setAmount(-$this->toCents($discountAmount));
            $discount->setDisplayText('Discount');
            $totals[] = $discount;
        }

        $total = $this->totalFactory->create();
        $total->setType(TotalInterface::TYPE_TOTAL);
        $total->setAmount($this->toCents((float) $quote->getGrandTotal()));
        $total->setDisplayText('Total');
        $totals[] = $total;

        return $totals;
    }

    /**
     * Extract masked cart ID from UCP format
     *
     * @param string $cartId
     * @return string
     */
    private function extractCartId(string $cartId): string
    {
        return str_replace('cart_', '', $cartId);
    }

    /**
     * Extract numeric product ID from UCP format
     *
     * @param string $productId
     * @return int
     */
    private function extractProductId(string $productId): int
    {
        return (int) str_replace('product_', '', $productId);
    }

    /**
     * Extract numeric item ID from UCP format
     *
     * @param string $itemId
     * @return int
     */
    private function extractItemId(string $itemId): int
    {
        return (int) str_replace('item_', '', $itemId);
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
