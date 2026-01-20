<?php
/**
 * UCP Checkout Session Management Interface
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;

interface CheckoutSessionManagementInterface
{
    /**
     * Create a new UCP checkout session from a Magento cart
     *
     * @param string $cartId Masked cart ID for guest, or cart ID for logged-in customer
     * @return \Aeqet\Ucp\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function create(string $cartId): CheckoutSessionInterface;

    /**
     * Get an existing UCP checkout session
     *
     * @param string $sessionId UCP session ID (e.g., ucp_abc123masked)
     * @return \Aeqet\Ucp\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(string $sessionId): CheckoutSessionInterface;

    /**
     * Update an existing UCP checkout session (full replacement)
     *
     * @param string $sessionId UCP session ID
     * @param \Aeqet\Ucp\Api\Data\CheckoutSessionInterface $checkoutSession
     * @return \Aeqet\Ucp\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function update(string $sessionId, CheckoutSessionInterface $checkoutSession): CheckoutSessionInterface;

    /**
     * Complete the checkout session and create a Magento order
     *
     * @param string $sessionId UCP session ID
     * @return \Aeqet\Ucp\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function complete(string $sessionId): CheckoutSessionInterface;

    /**
     * Cancel the checkout session
     *
     * @param string $sessionId UCP session ID
     * @return \Aeqet\Ucp\Api\Data\CheckoutSessionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function cancel(string $sessionId): CheckoutSessionInterface;
}
