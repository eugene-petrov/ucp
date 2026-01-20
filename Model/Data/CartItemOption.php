<?php
/**
 * UCP Cart Item Option Data Model
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data;

use Aeqet\Ucp\Api\Data\CartItemOptionInterface;

class CartItemOption implements CartItemOptionInterface
{
    /**
     * @var string
     */
    private string $code = '';

    /**
     * @var string
     */
    private string $value = '';

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @inheritDoc
     */
    public function setCode(string $code): CartItemOptionInterface
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @inheritDoc
     */
    public function setValue(string $value): CartItemOptionInterface
    {
        $this->value = $value;
        return $this;
    }
}
