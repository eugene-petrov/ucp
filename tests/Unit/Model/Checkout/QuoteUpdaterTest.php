<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Checkout;

use Aeqet\Ucp\Api\Data\AddressInterface;
use Aeqet\Ucp\Api\Data\BuyerInterface;
use Aeqet\Ucp\Model\Checkout\QuoteUpdater;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Quote\Model\Quote\Address\Rate as ShippingRate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteUpdaterTest extends TestCase
{
    private RegionFactory&MockObject $regionFactory;
    private QuoteUpdater $updater;

    protected function setUp(): void
    {
        $this->regionFactory = $this->createMock(RegionFactory::class);
        $this->updater = new QuoteUpdater($this->regionFactory);
    }

    private function makeRegionMock(?int $id): Region&MockObject
    {
        $region = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadByCode', 'loadByName', 'getId'])
            ->getMock();
        $region->method('loadByCode')->willReturnSelf();
        $region->method('loadByName')->willReturnSelf();
        $region->method('getId')->willReturn($id);
        return $region;
    }

    // --- buyer ---

    public function testApplyBuyerSetsBillingAddressFields(): void
    {
        $buyer = $this->createMock(BuyerInterface::class);
        $buyer->method('getEmail')->willReturn('john@example.com');
        $buyer->method('getFirstName')->willReturn('John');
        $buyer->method('getLastName')->willReturn('Doe');
        $buyer->method('getPhoneNumber')->willReturn('555-1234');

        $billing = $this->makeAddressMock();
        $billing->expects($this->once())->method('setEmail')->with('john@example.com');
        $billing->expects($this->once())->method('setFirstname')->with('John');
        $billing->expects($this->once())->method('setLastname')->with('Doe');
        $billing->expects($this->once())->method('setTelephone')->with('555-1234');

        $quote = $this->makeQuoteMock();
        $quote->method('getBillingAddress')->willReturn($billing);

        $this->updater->apply($quote, $buyer, null, null);
    }

    public function testApplyBuyerWithNoBillingAddressDoesNotFail(): void
    {
        $buyer = $this->createMock(BuyerInterface::class);
        $buyer->method('getEmail')->willReturn('a@b.com');
        $buyer->method('getFirstName')->willReturn(null);
        $buyer->method('getLastName')->willReturn(null);
        $buyer->method('getPhoneNumber')->willReturn(null);

        $quote = $this->makeQuoteMock();
        $quote->method('getBillingAddress')->willReturn(null);

        $this->updater->apply($quote, $buyer, null, null);

        $this->addToAssertionCount(1);
    }

    // --- fulfillment address ---

    public function testApplyFulfillmentAddressSetsShippingFields(): void
    {
        $addr = $this->createMock(AddressInterface::class);
        $addr->method('getFirstName')->willReturn('Jane');
        $addr->method('getLastName')->willReturn('Smith');
        $addr->method('getStreetLine1')->willReturn('123 Main St');
        $addr->method('getStreetLine2')->willReturn(null);
        $addr->method('getCity')->willReturn('Springfield');
        $addr->method('getState')->willReturn('IL');
        $addr->method('getPostalCode')->willReturn('62701');
        $addr->method('getCountryCode')->willReturn('US');
        $addr->method('getPhone')->willReturn('555-9999');

        $this->regionFactory->method('create')->willReturn($this->makeRegionMock(23));

        $shipping = $this->makeAddressMock();
        $shipping->expects($this->once())->method('setFirstname')->with('Jane');
        $shipping->expects($this->once())->method('setLastname')->with('Smith');
        $shipping->expects($this->once())->method('setStreet')->with(['123 Main St']);
        $shipping->expects($this->once())->method('setCity')->with('Springfield');
        $shipping->expects($this->once())->method('setRegion')->with('IL');
        $shipping->expects($this->once())->method('setRegionId')->with(23);
        $shipping->expects($this->once())->method('setPostcode')->with('62701');
        $shipping->expects($this->once())->method('setCountryId')->with('US');
        $shipping->expects($this->once())->method('setTelephone')->with('555-9999');

        // billing already has a street → no copy
        $billing = $this->makeAddressMock();
        $billing->method('getStreetLine')->with(1)->willReturn('existing street');

        $quote = $this->makeQuoteMock();
        $quote->method('getShippingAddress')->willReturn($shipping);
        $quote->method('getBillingAddress')->willReturn($billing);

        $this->updater->apply($quote, null, $addr, null);
    }

    public function testApplyFulfillmentAddressSkipsRegionIdWhenNotFound(): void
    {
        $addr = $this->createMock(AddressInterface::class);
        $addr->method('getFirstName')->willReturn('Jane');
        $addr->method('getLastName')->willReturn('Smith');
        $addr->method('getStreetLine1')->willReturn('1 Test St');
        $addr->method('getStreetLine2')->willReturn(null);
        $addr->method('getCity')->willReturn('Unknown City');
        $addr->method('getState')->willReturn('XX');
        $addr->method('getPostalCode')->willReturn('00000');
        $addr->method('getCountryCode')->willReturn('US');
        $addr->method('getPhone')->willReturn(null);

        $this->regionFactory->method('create')->willReturn($this->makeRegionMock(null));

        $shipping = $this->makeAddressMock();
        $shipping->expects($this->never())->method('setRegionId');

        $billing = $this->makeAddressMock();
        $billing->method('getStreetLine')->with(1)->willReturn('existing');

        $quote = $this->makeQuoteMock();
        $quote->method('getShippingAddress')->willReturn($shipping);
        $quote->method('getBillingAddress')->willReturn($billing);

        $this->updater->apply($quote, null, $addr, null);
    }

    public function testApplyFulfillmentAddressCopiesFieldsToBillingWhenEmpty(): void
    {
        $addr = $this->createMock(AddressInterface::class);
        $addr->method('getFirstName')->willReturn('Jane');
        $addr->method('getLastName')->willReturn('Smith');
        $addr->method('getStreetLine1')->willReturn('456 Elm Ave');
        $addr->method('getStreetLine2')->willReturn(null);
        $addr->method('getCity')->willReturn('Portland');
        $addr->method('getState')->willReturn('OR');
        $addr->method('getPostalCode')->willReturn('97201');
        $addr->method('getCountryCode')->willReturn('US');
        $addr->method('getPhone')->willReturn(null);

        $this->regionFactory->method('create')->willReturn($this->makeRegionMock(49));

        $shipping = $this->makeAddressMock();

        $billing = $this->makeAddressMock();
        $billing->method('getStreetLine')->with(1)->willReturn('');
        $billing->expects($this->once())->method('setStreet')->with(['456 Elm Ave']);
        $billing->expects($this->once())->method('setCity')->with('Portland');
        $billing->expects($this->once())->method('setCountryId')->with('US');

        $quote = $this->makeQuoteMock();
        $quote->method('getShippingAddress')->willReturn($shipping);
        $quote->method('getBillingAddress')->willReturn($billing);

        $this->updater->apply($quote, null, $addr, null);
    }

    // --- shipping method ---

    public function testApplyValidShippingMethodDoesNotThrow(): void
    {
        $rate = $this->makeRateMock('flatrate_flatrate');

        $shipping = $this->makeAddressMock();
        $shipping->method('getAllShippingRates')->willReturn([$rate]);

        $quote = $this->makeQuoteMock();
        $quote->method('getShippingAddress')->willReturn($shipping);

        $this->updater->apply($quote, null, null, 'flatrate_flatrate');

        $this->addToAssertionCount(1);
    }

    public function testApplyShippingMethodThrowsWhenMethodNotAvailable(): void
    {
        $rate = $this->makeRateMock('flatrate_flatrate');

        $shipping = $this->makeAddressMock();
        $shipping->method('getAllShippingRates')->willReturn([$rate]);

        $quote = $this->makeQuoteMock();
        $quote->method('getShippingAddress')->willReturn($shipping);

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('The selected shipping method "ups_ground" is not available.');

        $this->updater->apply($quote, null, null, 'ups_ground');
    }

    // --- null params ---

    public function testApplyWithAllNullsDoesNothing(): void
    {
        $quote = $this->makeQuoteMock();
        $quote->expects($this->never())->method('getBillingAddress');
        $quote->expects($this->never())->method('getShippingAddress');

        $this->updater->apply($quote, null, null, null);
    }

    // --- helpers ---

    /**
     * Quote mock: existing methods in onlyMethods, magic setters in addMethods.
     * PHPUnit 10 requires onlyMethods() when addMethods() is also used.
     */
    private function makeQuoteMock(): Quote&MockObject
    {
        $mock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getBillingAddress', 'getShippingAddress', 'getExtensionAttributes'])
            ->addMethods(['setCustomerEmail', 'setCustomerFirstname', 'setCustomerLastname'])
            ->getMock();
        $mock->method('getExtensionAttributes')->willReturn(null);
        return $mock;
    }

    /** QuoteAddress mock — setCollectShippingRates/setSameAsBilling are magic (not configurable) */
    private function makeAddressMock(): QuoteAddress&MockObject
    {
        return $this->getMockBuilder(QuoteAddress::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'setEmail', 'setFirstname', 'setLastname', 'setTelephone',
                'setStreet', 'setCity', 'setRegion', 'setRegionId', 'setPostcode', 'setCountryId',
                'getAllShippingRates', 'getStreetLine', 'collectShippingRates',
                'setSameAsBilling',
            ])
            ->addMethods(['setCollectShippingRates', 'setShippingMethod'])
            ->getMock();
    }

    /** ShippingRate mock — getCode() is a magic getter (DataObject) */
    private function makeRateMock(string $code): ShippingRate&MockObject
    {
        $rate = $this->getMockBuilder(ShippingRate::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCode'])
            ->getMock();
        $rate->method('getCode')->willReturn($code);
        return $rate;
    }
}
