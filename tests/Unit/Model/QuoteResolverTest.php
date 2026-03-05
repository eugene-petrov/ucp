<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model;

use Aeqet\Ucp\Model\Checkout\QuoteResolver;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\QuoteIdToMaskedQuoteIdInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class QuoteResolverTest extends TestCase
{
    private CartRepositoryInterface&MockObject $cartRepository;
    private MaskedQuoteIdToQuoteIdInterface&MockObject $maskedToId;
    private QuoteIdToMaskedQuoteIdInterface&MockObject $idToMasked;
    private LoggerInterface&MockObject $logger;
    private QuoteResolver $resolver;

    protected function setUp(): void
    {
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->maskedToId = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $this->idToMasked = $this->createMock(QuoteIdToMaskedQuoteIdInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->resolver = new QuoteResolver(
            $this->cartRepository,
            $this->maskedToId,
            $this->idToMasked,
            $this->logger
        );
    }

    // ---- resolveByCartId ----

    public function testResolveByCartIdWithMaskedId(): void
    {
        $maskedId = 'abc123masked';
        $quote = $this->makeQuote(42);

        $this->maskedToId->method('execute')->with($maskedId)->willReturn(42);
        $this->cartRepository->method('get')->with(42)->willReturn($quote);
        $this->idToMasked->method('execute')->with(42)->willReturn($maskedId);

        [$resolvedQuote, $resolvedMasked] = $this->resolver->resolveByCartId($maskedId);

        $this->assertSame($quote, $resolvedQuote);
        $this->assertSame($maskedId, $resolvedMasked);
    }

    public function testResolveByCartIdFallsBackToNumericId(): void
    {
        $numericCartId = '99';
        $quote = $this->makeQuote(99);

        $this->maskedToId->method('execute')
            ->willThrowException(new NoSuchEntityException());
        $this->cartRepository->method('get')->with(99)->willReturn($quote);
        $this->idToMasked->method('execute')->with(99)->willReturn('maskedFor99');

        [$resolvedQuote, $resolvedMasked] = $this->resolver->resolveByCartId($numericCartId);

        $this->assertSame($quote, $resolvedQuote);
        $this->assertSame('maskedFor99', $resolvedMasked);
    }

    public function testResolveByCartIdFallsBackToCartIdWhenMaskedIdFails(): void
    {
        $numericCartId = '77';
        $quote = $this->makeQuote(77);

        $this->maskedToId->method('execute')
            ->willThrowException(new NoSuchEntityException());
        $this->cartRepository->method('get')->with(77)->willReturn($quote);
        $this->idToMasked->method('execute')
            ->willThrowException(new NoSuchEntityException());

        [$resolvedQuote, $resolvedMasked] = $this->resolver->resolveByCartId($numericCartId);

        $this->assertSame($quote, $resolvedQuote);
        $this->assertSame($numericCartId, $resolvedMasked);
    }

    public function testResolveByCartIdThrowsWhenNonNumericAndMaskedFails(): void
    {
        $this->maskedToId->method('execute')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->resolver->resolveByCartId('not-a-number-and-invalid-masked');
    }

    public function testResolveByCartIdThrowsWhenCartNotFound(): void
    {
        $this->maskedToId->method('execute')->with('99')->willReturn(99);
        $this->cartRepository->method('get')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->resolver->resolveByCartId('99');
    }

    // ---- resolveByMaskedId ----

    public function testResolveByMaskedIdReturnsQuote(): void
    {
        $maskedId = 'someMasked';
        $quote = $this->makeQuote(5);

        $this->maskedToId->method('execute')->with($maskedId)->willReturn(5);
        $this->cartRepository->method('get')->with(5)->willReturn($quote);

        $result = $this->resolver->resolveByMaskedId($maskedId);

        $this->assertSame($quote, $result);
    }

    public function testResolveByMaskedIdThrowsWhenNotFound(): void
    {
        $this->maskedToId->method('execute')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);

        $this->resolver->resolveByMaskedId('invalid-masked');
    }

    // ---- helpers ----

    private function makeQuote(int $id): CartInterface&MockObject
    {
        $quote = $this->createMock(CartInterface::class);
        $quote->method('getId')->willReturn((string) $id);
        return $quote;
    }
}
