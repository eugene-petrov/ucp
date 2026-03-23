<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Checkout;

use Aeqet\Ucp\Api\Data\CheckoutSessionInterface;
use Aeqet\Ucp\Api\Data\OrderConfirmationInterface;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionCompleter;
use Aeqet\Ucp\Model\Checkout\CheckoutSessionRepository;
use Aeqet\Ucp\Model\Checkout\OrderPlacer;
use Aeqet\Ucp\Model\Config\Config;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class CheckoutSessionCompleterTest extends TestCase
{
    private OrderPlacer&MockObject $orderPlacer;
    private CheckoutSessionRepository&MockObject $sessionRepository;
    private Config&MockObject $config;
    private LoggerInterface&MockObject $logger;
    private CheckoutSessionCompleter $completer;

    protected function setUp(): void
    {
        $this->orderPlacer = $this->createMock(OrderPlacer::class);
        $this->sessionRepository = $this->createMock(CheckoutSessionRepository::class);
        $this->config = $this->createMock(Config::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->completer = new CheckoutSessionCompleter(
            $this->orderPlacer,
            $this->sessionRepository,
            $this->config,
            $this->logger
        );
    }

    // --- happy path ---

    public function testCompletePlacesOrderAndMarksSessionCompleted(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE);

        $confirmation = $this->createMock(OrderConfirmationInterface::class);

        $quote = $this->makeQuoteMock();
        $quote->method('getId')->willReturn('10');

        $this->config->method('getDefaultPaymentMethod')->willReturn('checkmo');
        $this->orderPlacer->expects($this->once())->method('place')->with(10, 'checkmo')->willReturn($confirmation);

        $session->expects($this->once())->method('setStatus')->with(CheckoutSessionInterface::STATUS_COMPLETED);
        $session->expects($this->once())->method('setOrder')->with($confirmation);
        $this->sessionRepository->expects($this->once())->method('save')->with($session, 'masked1');

        $result = $this->completer->complete($session, $quote, 'masked1', 'sess1');

        $this->assertSame($session, $result);
    }

    // --- not ready ---

    public function testCompleteThrowsWhenSessionNotReadyAndAllFieldsMissing(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_INCOMPLETE);

        $quote = $this->makeQuoteMock();
        $quote->method('getCustomerEmail')->willReturn('');
        $quote->method('getBillingAddress')->willReturn(null);
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getShippingAddress')->willReturn(null);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('not ready to be completed');

        $this->completer->complete($session, $quote, 'masked1', 'sess1');
    }

    public function testCompleteThrowsWithSpecificMissingFieldsListed(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_INCOMPLETE);

        $billing = $this->makeAddressMock();
        $billing->method('getStreetLine')->with(1)->willReturn('');

        $shippingWithStreet = $this->makeAddressMock();
        $shippingWithStreet->method('getStreetLine')->with(1)->willReturn('100 Main St');
        $shippingWithStreet->method('getShippingMethod')->willReturn(null);

        $quote = $this->makeQuoteMock();
        $quote->method('getCustomerEmail')->willReturn('test@example.com');
        $quote->method('getBillingAddress')->willReturn($billing);
        $quote->method('isVirtual')->willReturn(false);
        $quote->method('getShippingAddress')->willReturn($shippingWithStreet);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Missing: billing address, shipping method');

        $this->completer->complete($session, $quote, 'masked1', 'sess1');
    }

    // --- order placer error ---

    public function testCompleteThrowsLocalizedException_whenOrderPlacerFails(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_READY_FOR_COMPLETE);

        $quote = $this->makeQuoteMock();
        $quote->method('getId')->willReturn('5');

        $this->config->method('getDefaultPaymentMethod')->willReturn('checkmo');
        $this->orderPlacer->method('place')->willThrowException(new RuntimeException('DB error'));

        $this->logger->expects($this->once())->method('error');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Failed to complete checkout. Please try again.');

        $this->completer->complete($session, $quote, 'masked1', 'sess1');
    }

    // --- virtual quote ---

    public function testCompleteThrowsForVirtualQuoteWhenEmailAndBillingMissing(): void
    {
        $session = $this->createMock(CheckoutSessionInterface::class);
        $session->method('getStatus')->willReturn(CheckoutSessionInterface::STATUS_INCOMPLETE);

        $quote = $this->makeQuoteMock();
        $quote->method('getCustomerEmail')->willReturn('');
        $quote->method('getBillingAddress')->willReturn(null);
        $quote->method('isVirtual')->willReturn(true);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Missing: email, billing address');

        $this->completer->complete($session, $quote, 'masked1', 'sess1');
    }

    // --- helpers ---

    /**
     * Quote mock: existing methods in onlyMethods, magic getCustomerEmail in addMethods.
     * PHPUnit 10 requires onlyMethods() when addMethods() is also used.
     */
    private function makeQuoteMock(): Quote&MockObject
    {
        return $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress', 'getShippingAddress', 'isVirtual', 'getId'])
            ->addMethods(['getCustomerEmail'])
            ->getMock();
    }

    private function makeAddressMock(): QuoteAddress&MockObject
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStreetLine', 'getShippingMethod'])
            ->getMock();
    }
}
