<?php
/**
 * UCP Cart Item Option Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

/**
 * Interface for UCP Cart Item Option data
 *
 * @api
 */
interface CartItemOptionInterface
{
    /**
     * Get option code (attribute code)
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Set option code
     *
     * @param string $code
     * @return $this
     */
    public function setCode(string $code): self;

    /**
     * Get option value
     *
     * @return string
     */
    public function getValue(): string;

    /**
     * Set option value
     *
     * @param string $value
     * @return $this
     */
    public function setValue(string $value): self;
}
