<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Checkout;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionRepository;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionSynchronizer;
use Aeqet\Ucp\Model\Checkout\QuoteResolver;
use Aeqet\Ucp\Model\Checkout\QuoteToUcpConverter;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckoutSessionSynchronizerTest extends TestCase
{
    private QuoteResolver&MockObject $quoteResolver;
    private QuoteToUcpConverter&MockObject $quoteToUcpConverter;
    private CheckoutSessionRepository&MockObject $sessionRepository;
    private LoggerInterface&MockObject $logger;
    private CheckoutSessionSynchronizer $synchronizer;

    protected function setUp(): void
    {
        $this->quoteResolver = $this->createMock(QuoteResolver::class);
        $this->quoteToUcpConverter = $this->createMock(QuoteToUcpConverter::class);
        $this->sessionRepository = $this->createMock(CheckoutSessionRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->synchronizer = new CheckoutSessionSynchronizer(
            $this->quoteResolver,
            $this->quoteToUcpConverter,
            $this->sessionRepository,
            $this->logger
        );
    }

    // --- syncFromQuote ---

    public function testSyncFromQuoteConvertsAndSavesSession(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $session = $this->createMock(CheckoutSessionInterface::class);

        $this->quoteToUcpConverter->expects($this->once())
            ->method('convert')
            ->with($quote, 'abc123')
            ->willReturn($session);

        $this->sessionRepository->expects($this->once())
            ->method('save')
            ->with($session, 'abc123');

        $result = $this->synchronizer->syncFromQuote($quote, 'abc123');

        $this->assertSame($session, $result);
    }

    // --- refresh ---

    public function testRefreshReturnsSessionDirectlyWhenCompleted(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_COMPLETED);

        $this->sessionRepository->method('get')->with('sess1')->willReturn($session);

        $this->quoteToUcpConverter->expects($this->never())->method('convert');

        $result = $this->synchronizer->refresh('sess1');

        $this->assertSame($session, $result);
    }

    public function testRefreshReturnsSessionDirectlyWhenCanceled(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_CANCELED);

        $this->sessionRepository->method('get')->with('sess1')->willReturn($session);

        $this->quoteToUcpConverter->expects($this->never())->method('convert');

        $result = $this->synchronizer->refresh('sess1');

        $this->assertSame($session, $result);
    }

    public function testRefreshResyncsFromQuoteWhenIncomplete(): void
    {
        $existingSession = $this->createMock(CheckoutSessionInterface::class);
        $existingSession->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_INCOMPLETE);

        $freshSession = $this->createMock(CheckoutSessionInterface::class);
        $quote = $this->createMock(CartInterface::class);

        $this->sessionRepository->method('get')->with('sess1')->willReturn($existingSession);
        $this->sessionRepository->method('getMaskedQuoteId')->with('sess1')->willReturn('masked1');
        $this->quoteResolver->method('resolveByMaskedId')->with('masked1')->willReturn($quote);
        $this->quoteToUcpConverter->method('convert')->with($quote, 'masked1')->willReturn($freshSession);

        $this->sessionRepository->expects($this->once())->method('save')->with($freshSession, 'masked1');

        $result = $this->synchronizer->refresh('sess1');

        $this->assertSame($freshSession, $result);
    }

    // --- reconstruct ---

    public function testReconstructStripsUcpPrefixBeforeResolvingMaskedId(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $session = $this->createMock(CheckoutSessionInterface::class);

        $this->quoteResolver->expects($this->once())
            ->method('resolveByMaskedId')
            ->with('abc123')
            ->willReturn($quote);

        $this->quoteToUcpConverter->method('convert')->with($quote, 'abc123')->willReturn($session);

        $result = $this->synchronizer->reconstruct('ucp_abc123');

        $this->assertSame($session, $result);
    }

    public function testReconstructUsesSessionIdDirectlyWithoutPrefix(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $session = $this->createMock(CheckoutSessionInterface::class);

        $this->quoteResolver->expects($this->once())
            ->method('resolveByMaskedId')
            ->with('plainid')
            ->willReturn($quote);

        $this->quoteToUcpConverter->method('convert')->with($quote, 'plainid')->willReturn($session);

        $result = $this->synchronizer->reconstruct('plainid');

        $this->assertSame($session, $result);
    }

    public function testReconstructThrowsWhenQuoteNotFound(): void
    {
        $this->quoteResolver->method('resolveByMaskedId')
            ->willThrowException(new NoSuchEntityException());

        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage('UCP Checkout Session with ID "ucp_missing" does not exist.');

        $this->synchronizer->reconstruct('ucp_missing');
    }
}
