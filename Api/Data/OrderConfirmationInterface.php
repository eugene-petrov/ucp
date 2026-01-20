<?php
/**
 * UCP Order Confirmation Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api\Data;

interface OrderConfirmationInterface
{
    /**
     * Get order ID
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Set order ID
     *
     * @param string $id
     * @return $this
     */
    public function setId(string $id): self;

    /**
     * Get permalink URL to order page
     *
     * @return string
     */
    public function getPermalinkUrl(): string;

    /**
     * Set permalink URL
     *
     * @param string $permalinkUrl
     * @return $this
     */
    public function setPermalinkUrl(string $permalinkUrl): self;
}
