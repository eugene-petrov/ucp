<?php
/**
 * UCP Variant Attribute Interface
 *
 * Represents an attribute of a product variant (e.g., color, size).
 * Used for proper WebAPI serialization.
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface VariantAttributeInterface
{
    /**
     * Get attribute code (e.g., "color", "size")
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Set attribute code
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): self;

    /**
     * Get attribute value (e.g., "Blue", "M")
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Set attribute value
     *
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self;
}
