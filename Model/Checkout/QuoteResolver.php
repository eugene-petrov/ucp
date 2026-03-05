<?php
/**
 * UCP Quote Resolver — resolves cart/masked IDs to loaded CartInterface instances
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Checkout;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use Psr\Log\LoggerInterface;

class QuoteResolver
{
    /**
     * @param CartRepositoryInterface $cartRepository
     * @param MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId
     * @param QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private readonly QuoteIdToMaskedQuoteIdInterface $quoteIdToMaskedQuoteId,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Resolve cart ID (masked or numeric) to [CartInterface, string $maskedId].
     *
     * Used in create().
     *
     * @param string $cartId
     * @return array{0: CartInterface, 1: string}
     * @throws NoSuchEntityException
     */
    public function resolveByCartId(string $cartId): array
    {
        $quoteId = $this->resolveNumericId($cartId);
        $quote = $this->loadQuote($quoteId, $cartId);
        $maskedId = $this->resolveMaskedId((int) $quote->getId(), $cartId);
        return [$quote, $maskedId];
    }

    /**
     * Resolve masked ID to CartInterface.
     *
     * Used in get() / update() / complete().
     *
     * @param string $maskedId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function resolveByMaskedId(string $maskedId): CartInterface
    {
        $quoteId = $this->maskedQuoteIdToQuoteId->execute($maskedId);
        return $this->cartRepository->get($quoteId);
    }

    /**
     * Convert a cart ID to a numeric quote ID.
     *
     * @param string $cartId
     * @return int
     * @throws NoSuchEntityException
     */
    private function resolveNumericId(string $cartId): int
    {
        try {
            return $this->maskedQuoteIdToQuoteId->execute($cartId);
        } catch (Exception $e) {
            $this->logger->debug('Unable to convert masked quote ID, trying as numeric', [
                'cart_id' => $cartId,
                'exception' => $e->getMessage(),
            ]);
            if (is_numeric($cartId)) {
                return (int) $cartId;
            }
        }
        throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
    }

    /**
     * Load a quote by numeric ID, mapping not-found to a user-friendly exception.
     *
     * @param int $quoteId
     * @param string $cartId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    private function loadQuote(int $quoteId, string $cartId): CartInterface
    {
        try {
            return $this->cartRepository->get($quoteId);
        } catch (NoSuchEntityException $e) {
            $this->logger->warning('Cart not found', [
                'cart_id' => $cartId,
                'quote_id' => $quoteId,
                'exception' => $e->getMessage(),
            ]);
            throw new NoSuchEntityException(__('Cart with ID "%1" does not exist.', $cartId));
        }
    }

    /**
     * Resolve a numeric quote ID to its masked ID, falling back to the provided value.
     *
     * @param int $quoteId
     * @param string $fallback
     * @return string
     */
    private function resolveMaskedId(int $quoteId, string $fallback): string
    {
        try {
            return $this->quoteIdToMaskedQuoteId->execute($quoteId);
        } catch (Exception $e) {
            $this->logger->debug('Unable to get masked quote ID, using cart ID', [
                'quote_id' => $quoteId,
                'exception' => $e->getMessage(),
            ]);
            return $fallback;
        }
    }
}
