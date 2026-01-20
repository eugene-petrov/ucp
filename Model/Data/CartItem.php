<?php
/**
 * UCP Cart Item Data Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CartItemInterface;
use Aeqet\Ucp\Api\Data\CatalogProductInterface;

class CartItem implements CartItemInterface
{
    /**
     * @var string
     */
    private string $id = '';

    /**
     * @var int
     */
    private int $quantity = 0;

    /**
     * @var int
     */
    private int $price = 0;

    /**
     * @var int
     */
    private int $subtotal = 0;

    /**
     * @var CatalogProductInterface|null
     */
    private ?CatalogProductInterface $product = null;

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): CartItemInterface
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @inheritDoc
     */
    public function setQuantity(int $quantity): CartItemInterface
    {
        $this->quantity = $quantity;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @inheritDoc
     */
    public function setPrice(int $price): CartItemInterface
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    /**
     * @inheritDoc
     */
    public function setSubtotal(int $subtotal): CartItemInterface
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getProduct(): CatalogProductInterface
    {
        return $this->product;
    }

    /**
     * @inheritDoc
     */
    public function setProduct(CatalogProductInterface $product): CartItemInterface
    {
        $this->product = $product;
        return $this;
    }
}
