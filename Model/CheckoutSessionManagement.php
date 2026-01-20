<?php
/**
 * UCP Checkout Session Management Implementation
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\CheckoutSessionManagementInterface;
use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterfaceFactory;
use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class CheckoutSessionManagement implements CheckoutSessionManagementInterface
{
    /**
     * Constructor
     *
     * @param CartRepositoryInterface $cartRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param QuoteToUcpConverter $quoteToUcpConverter
     * @param CheckoutSessionRepository $sessionRepository
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderConfirmationInterfaceFactory $orderConfirmationFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly QuoteToUcpConverter $quoteToUcpConverter,
        private readonly CheckoutSessionRepository $sessionRepository,
        private readonly CartManagementInterface $cartManagement,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderConfirmationInterfaceFactory $orderConfirmationFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function create(string $cartId): CheckoutSessionInterface
    {
        if (empty($cartId)) {
            throw new NoSuchEntityException(__('Cart ID is required to create a UCP checkout session.'));
        }

        $existingSessionId = $this->sessionRepository->getSessionIdByQuoteId($cartId);
        if ($existingSessionId) {
            return $this->sessionRepository->get($existingSessionId);
        }

        $quoteId = null;
        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (Exception $e) {
            $this->logger->debug('Unable to convert masked quote ID, trying as numeric', [
                'cart_id' => $cartId,
                'exception' => $e->getMessage()
            ]);
            if (is_numeric($cartId)) {
                $quoteId = (int) $cartId;
            }
        }

        if ($quoteId === null || $quoteId === 0) {
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
        }

        try {
            $quote = $this->cartRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Cart not found', [
                'cart_id' => $cartId,
                'quote_id' => $quoteId,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
        }

        try {
            $maskedId = $this->quoteIdToMaskedQuoteId->execute((int) $quote->getId());
        } catch (Exception $e) {
            $this->logger->debug('Unable to get masked quote ID, using cart ID', [
                'quote_id' => $quote->getId(),
                'exception' => $e->getMessage()
            ]);
            $maskedId = $cartId;
        }

        $session = $this->quoteToUcpConverter->convert($quote, $maskedId);
        $this->sessionRepository->save($session, $maskedId);

        return $session;
    }

    /**
     * @inheritDoc
     */
    public function get(string $sessionId): CheckoutSessionInterface
    {
        if ($this->sessionRepository->exists($sessionId)) {
            $session = $this->sessionRepository->get($sessionId);
            if ($session->getStatus() !== CheckoutSessionInterface::STATUS_COMPLETED &&
                $session->getStatus() !== CheckoutSessionInterface::STATUS_CANCELED
            ) {
                $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
                $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedId);
                $quote = $this->cartRepository->get($quoteId);

                $session = $this->quoteToUcpConverter->convert($quote, $maskedId);
                $this->sessionRepository->save($session, $maskedId);
            }

            return $session;
        }

        $maskedId = $this->extractMaskedId($sessionId);

        try {
            $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedId);
            if ($quoteId === null) {
                throw new NoSuchEntityException(
                    __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
                );
            }
            $quote = $this->cartRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Checkout session not found', [
                'session_id' => $sessionId,
                'masked_id' => $maskedId,
                'exception' => $e->getMessage()
            ]);
            throw new NoSuchEntityException(
                __('UCP Checkout Session with ID "%1" does not exist.', $sessionId)
            );
        }

        $session = $this->quoteToUcpConverter->convert($quote, $maskedId);
        $this->sessionRepository->save($session, $maskedId);

        return $session;
    }

    /**
     * @inheritDoc
     */
    public function update(string $sessionId, CheckoutSessionInterface $checkoutSession): CheckoutSessionInterface
    {
        $existingSession = $this->get($sessionId);

        if ($existingSession->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            throw new LocalizedException(__('Cannot update a completed checkout session.'));
        }

        if ($existingSession->getStatus() === CheckoutSessionInterface::STATUS_CANCELED) {
            throw new LocalizedException(__('Cannot update a canceled checkout session.'));
        }

        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedId);
        $quote = $this->cartRepository->get($quoteId);

        $buyer = $checkoutSession->getBuyer();
        if ($buyer) {
            if ($buyer->getEmail()) {
                $quote->setCustomerEmail($buyer->getEmail());
            }
            if ($buyer->getFirstName()) {
                $quote->setCustomerFirstname($buyer->getFirstName());
            }
            if ($buyer->getLastName()) {
                $quote->setCustomerLastname($buyer->getLastName());
            }

            $billingAddress = $quote->getBillingAddress();
            if ($billingAddress) {
                if ($buyer->getEmail()) {
                    $billingAddress->setEmail($buyer->getEmail());
                }
                if ($buyer->getFirstName()) {
                    $billingAddress->setFirstname($buyer->getFirstName());
                }
                if ($buyer->getLastName()) {
                    $billingAddress->setLastname($buyer->getLastName());
                }
                if ($buyer->getPhoneNumber()) {
                    $billingAddress->setTelephone($buyer->getPhoneNumber());
                }
            }
        }

        $this->cartRepository->save($quote);

        $updatedSession = $this->quoteToUcpConverter->convert($quote, $maskedId);
        $this->sessionRepository->save($updatedSession, $maskedId);

        return $updatedSession;
    }

    /**
     * @inheritDoc
     */
    public function complete(string $sessionId): CheckoutSessionInterface
    {
        $session = $this->get($sessionId);

        if ($session->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            return $session;
        }

        if ($session->getStatus() === CheckoutSessionInterface::STATUS_CANCELED) {
            throw new LocalizedException(__('Cannot complete a canceled checkout session.'));
        }

        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedId);
        $quote = $this->cartRepository->get($quoteId);

        $quote->getPayment()->setMethod('checkmo');
        $this->cartRepository->save($quote);

        try {
            $orderId = $this->cartManagement->placeOrder($quoteId);
            $order = $this->orderRepository->get($orderId);

            $orderConfirmation = $this->orderConfirmationFactory->create();
            $orderConfirmation->setId($order->getIncrementId());

            $baseUrl = $this->storeManager->getStore()->getBaseUrl();
            $orderConfirmation->setPermalinkUrl(
                $baseUrl . 'sales/order/view/order_id/' . $order->getEntityId()
            );

            $session->setStatus(CheckoutSessionInterface::STATUS_COMPLETED);
            $session->setOrder($orderConfirmation);
            $this->sessionRepository->save($session, $maskedId);

            return $session;
        } catch (Exception $e) {
            $this->logger->error('Failed to complete checkout', [
                'session_id' => $sessionId,
                'quote_id' => $quoteId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new LocalizedException(
                __('Failed to complete checkout: %1', $e->getMessage())
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function cancel(string $sessionId): CheckoutSessionInterface
    {
        $session = $this->get($sessionId);

        if ($session->getStatus() === CheckoutSessionInterface::STATUS_COMPLETED) {
            throw new LocalizedException(__('Cannot cancel a completed checkout session.'));
        }

        $maskedId = $this->sessionRepository->getMaskedQuoteId($sessionId);

        $session->setStatus(CheckoutSessionInterface::STATUS_CANCELED);
        $this->sessionRepository->save($session, $maskedId);

        return $session;
    }

    /**
     * Extract masked ID from session ID
     *
     * @param string $sessionId
     * @return string
     */
    private function extractMaskedId(string $sessionId): string
    {
        if (str_starts_with($sessionId, 'ucp_')) {
            return substr($sessionId, 4);
        }
        return $sessionId;
    }
}
