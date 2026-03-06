<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Capability;

use Aeqet\Ucp\Api\Data\CapabilityInterface;
use Aeqet\Ucp\Model\Capability\Negotiator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class NegotiatorTest extends TestCase
{
    private Negotiator $negotiator;

    protected function setUp(): void
    {
        $this->negotiator = new Negotiator();
    }

    private function makeCap(string $name): CapabilityInterface&MockObject
    {
        $cap = $this->createMock(CapabilityInterface::class);
        $cap->method('getName')->willReturn($name);
        return $cap;
    }

    public function testIntersectReturnsMatchingSubset(): void
    {
        $checkout = $this->makeCap('dev.ucp.shopping.checkout');
        $catalog = $this->makeCap('com.example.shopping.catalog');

        $result = $this->negotiator->intersect(
            [$checkout, $catalog],
            ['dev.ucp.shopping.checkout']
        );

        $this->assertCount(1, $result);
        $this->assertSame('dev.ucp.shopping.checkout', $result[0]->getName());
    }

    public function testIntersectWithNoOverlapReturnsEmpty(): void
    {
        $checkout = $this->makeCap('dev.ucp.shopping.checkout');

        $result = $this->negotiator->intersect(
            [$checkout],
            ['dev.ucp.shopping.other']
        );

        $this->assertSame([], $result);
    }

    public function testIntersectWithEmptyPlatformNamesIsFailOpen(): void
    {
        $checkout = $this->makeCap('dev.ucp.shopping.checkout');
        $catalog = $this->makeCap('com.example.shopping.catalog');

        $result = $this->negotiator->intersect([$checkout, $catalog], []);

        $this->assertCount(2, $result);
        $this->assertSame($checkout, $result[0]);
        $this->assertSame($catalog, $result[1]);
    }
}
