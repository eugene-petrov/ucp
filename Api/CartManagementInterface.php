<?php
/**
 * UCP Cart Management Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

use Aeqet\Ucp\Api\Data\CartInterface;

interface CartManagementInterface
{
    /**
     * Create a new cart
     *
     * @return \Aeqet\Ucp\Api\Data\CartInterface
     */
    public function create(): CartInterface;

    /**
     * Get cart by ID
     *
     * @param string $cartId Cart ID (with or without "cart_" prefix)
     * @return \Aeqet\Ucp\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $cartId): CartInterface;

    /**
     * Add item to cart
     *
     * For configurable products, you can either:
     * 1. Use the child/variant product ID directly as productId
     * 2. Use the parent product ID and specify options array with code/value pairs
     *
     * Example options for configurable product:
     * [
     *     {"code": "color", "value": "Blue"},
     *     {"code": "size", "value": "M"}
     * ]
     *
     * @param string $cartId Cart ID (with or without "cart_" prefix)
     * @param string $productId Product ID (with or without "product_" prefix)
     * @param int $quantity
     * @param \Aeqet\Ucp\Api\Data\CartItemOptionInterface[]|null $options Configurable options
     * @return \Aeqet\Ucp\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addItem(string $cartId, string $productId, int $quantity, ?array $options = null): CartInterface;

    /**
     * Update item quantity
     *
     * @param string $cartId Cart ID (with or without "cart_" prefix)
     * @param string $itemId Item ID (with or without "item_" prefix)
     * @param int $quantity
     * @return \Aeqet\Ucp\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateItem(string $cartId, string $itemId, int $quantity): CartInterface;

    /**
     * Remove item from cart
     *
     * @param string $cartId Cart ID (with or without "cart_" prefix)
     * @param string $itemId Item ID (with or without "item_" prefix)
     * @return \Aeqet\Ucp\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function removeItem(string $cartId, string $itemId): CartInterface;
}
