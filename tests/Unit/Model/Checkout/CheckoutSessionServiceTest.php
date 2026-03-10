<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Checkout;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionCompleter;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionRepository;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionService;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionSynchronizer;
use Aeqet\Ucp\Model\Checkout\QuoteResolver;
use Aeqet\Ucp\Model\Checkout\QuoteUpdater;
use Aeqet\Ucp\Model\ResourceModel\CheckoutSession as CheckoutSessionResource;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request as RestRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CheckoutSessionServiceTest extends TestCase
{
    private QuoteResolver&MockObject $quoteResolver;
    private CartRepositoryInterface&MockObject $cartRepository;
    private CheckoutSessionRepository&MockObject $sessionRepository;
    private CheckoutSessionSynchronizer&MockObject $sessionSynchronizer;
    private QuoteUpdater&MockObject $quoteUpdater;
    private CheckoutSessionCompleter&MockObject $sessionCompleter;
    private RestRequest&MockObject $request;
    private CheckoutSessionResource&MockObject $sessionResource;
    private LoggerInterface&MockObject $logger;
    private CheckoutSessionService $management;

    protected function setUp(): void
    {
        $this->quoteResolver = $this->createMock(QuoteResolver::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->sessionRepository = $this->createMock(CheckoutSessionRepository::class);
        $this->sessionSynchronizer = $this->createMock(CheckoutSessionSynchronizer::class);
        $this->quoteUpdater = $this->createMock(QuoteUpdater::class);
        $this->sessionCompleter = $this->createMock(CheckoutSessionCompleter::class);
        $this->request = $this->createMock(RestRequest::class);
        $this->sessionResource = $this->createMock(CheckoutSessionResource::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->management = new CheckoutSessionService(
            $this->quoteResolver,
            $this->cartRepository,
            $this->sessionRepository,
            $this->sessionSynchronizer,
            $this->quoteUpdater,
            $this->sessionCompleter,
            $this->request,
            $this->sessionResource,
            $this->logger,
        );
    }

    // --- create ---

    public function testCreateThrowsWhenCartIdIsEmpty(): void
    {
        $this->expectException(NoSuchEntityException::class);
        $this->management->create('');
    }

    public function testCreateReturnsExistingSessionWhenAlreadyCreated(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $this->sessionRepository->method('getSessionIdByQuoteId')->with('cartId')->willReturn('sess1');
        $this->sessionRepository->method('get')->with('sess1')->willReturn($session);

        $this->sessionSynchronizer->expects($this->never())->method('syncFromQuote');

        $result = $this->management->create('cartId');

        $this->assertSame($session, $result);
    }

    public function testCreateSyncsFromQuoteForNewSession(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $session = $this->createMock(CheckoutSessionInterface::class);

        $this->sessionRepository->method('getSessionIdByQuoteId')->willReturn(null);
        $this->quoteResolver->method('resolveByCartId')->with('cartId')->willReturn([$quote, 'masked1']);
        $this->sessionSynchronizer->expects($this->once())
            ->method('syncFromQuote')->with($quote, 'masked1')->willReturn($session);

        $result = $this->management->create('cartId');

        $this->assertSame($session, $result);
    }

    // --- get ---

    public function testGetDelegatesToRefreshWhenSessionExists(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $this->sessionRepository->method('exists')->with('sess1')->willReturn(true);
        $this->sessionSynchronizer->expects($this->once())
            ->method('refresh')->with('sess1')->willReturn($session);

        $result = $this->management->get('sess1');

        $this->assertSame($session, $result);
    }

    public function testGetDelegatesToReconstructWhenSessionAbsent(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $this->sessionRepository->method('exists')->with('sess1')->willReturn(false);
        $this->sessionSynchronizer->expects($this->once())
            ->method('reconstruct')->with('sess1')->willReturn($session);

        $result = $this->management->get('sess1');

        $this->assertSame($session, $result);
    }

    // --- update ---

    public function testUpdateThrowsWhenSessionCompleted(): void
    {
        $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_COMPLETED);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot update a completed checkout session.');

        $this->management->update('sess1');
    }

    public function testUpdateThrowsWhenSessionCanceled(): void
    {
        $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_CANCELED);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot update a canceled checkout session.');

        $this->management->update('sess1');
    }

    public function testUpdateAppliesChangesAndResyncsFromQuote(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $updatedSession = $this->createMock(CheckoutSessionInterface::class);

        $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_INCOMPLETE);
        $this->sessionRepository->method('getMaskedQuoteId')->with('sess1')->willReturn('masked1');
        $this->quoteResolver->method('resolveByMaskedId')->with('masked1')->willReturn($quote);

        $this->quoteUpdater->expects($this->once())->method('apply')->with($quote, null, null, null);
        $this->cartRepository->expects($this->once())->method('save')->with($quote);
        $this->sessionSynchronizer->method('syncFromQuote')->with($quote, 'masked1')
            ->willReturn($updatedSession);

        $result = $this->management->update('sess1');

        $this->assertSame($updatedSession, $result);
    }

    // --- complete ---

    public function testCompleteReturnsSessionWhenAlreadyCompleted(): void
    {
        $session = $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_COMPLETED);

        $this->sessionCompleter->expects($this->never())->method('complete');

        $result = $this->management->complete('sess1');

        $this->assertSame($session, $result);
    }

    public function testCompleteThrowsWhenSessionCanceled(): void
    {
        $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_CANCELED);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot complete a canceled checkout session.');

        $this->management->complete('sess1');
    }

    public function testCompleteDelegatesToCompleter(): void
    {
        $quote = $this->createMock(CartInterface::class);
        $completedSession = $this->createMock(CheckoutSessionInterface::class);
        $session = $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE);

        $this->sessionRepository->method('getMaskedQuoteId')->with('sess1')->willReturn('masked1');
        $this->quoteResolver->method('resolveByMaskedId')->with('masked1')->willReturn($quote);
        $this->sessionCompleter->expects($this->once())
            ->method('complete')->with($session, $quote, 'masked1', 'sess1')
            ->willReturn($completedSession);

        $result = $this->management->complete('sess1');

        $this->assertSame($completedSession, $result);
    }

    // --- cancel ---

    public function testCancelThrowsWhenSessionCompleted(): void
    {
        $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_COMPLETED);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Cannot cancel a completed checkout session.');

        $this->management->cancel('sess1');
    }

    public function testCancelSetsStatusAndSavesSession(): void
    {
        $session = $this->setupGetViaRefresh('sess1', CheckoutSessionInterface::STATUS_INCOMPLETE);
        $this->sessionRepository->method('getMaskedQuoteId')->with('sess1')->willReturn('masked1');

        $session->expects($this->once())
            ->method('setStatus')->with(CheckoutSessionInterface::STATUS_CANCELED);
        $this->sessionRepository->expects($this->once())
            ->method('save')->with($session, 'masked1');

        $result = $this->management->cancel('sess1');

        $this->assertSame($session, $result);
    }

    // --- helper ---

    /**
     * Configures sessionRepository->exists() + sessionSynchronizer->refresh()
     * so that management->get() returns a session with the given status.
     */
    private function setupGetViaRefresh(
        string $sessionId,
        string $status
    ): CheckoutSessionInterface&MockObject {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn($status);

        $this->sessionRepository->method('exists')->with($sessionId)->willReturn(true);
        $this->sessionSynchronizer->method('refresh')->with($sessionId)->willReturn($session);

        return $session;
    }
}
