<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Capability;

use Aeqet\Ucp\Model\Capability\PlatformProfileFetcher;
use Magento\Framework\HTTP\Client\Curl;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PlatformProfileFetcherTest extends TestCase
{
    private Curl&MockObject $curl;
    private LoggerInterface&MockObject $logger;
    private PlatformProfileFetcher $fetcher;

    protected function setUp(): void
    {
        $this->curl = $this->createMock(Curl::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->fetcher = new PlatformProfileFetcher($this->curl, $this->logger);
    }

    public function testSuccessfulFetchReturnsCapabilityNames(): void
    {
        $body = json_encode([
            'ucp' => [
                'capabilities' => [
                    'dev.ucp.shopping.checkout' => ['version' => '2026-01-23'],
                    'dev.ucp.shopping.catalog' => ['version' => '2026-01-23'],
                ]
            ]
        ]);

        $this->curl->expects($this->once())->method('get')->with('https://example.com/profile.json');
        $this->curl->method('getBody')->willReturn($body);

        $names = $this->fetcher->fetchCapabilityNames('https://example.com/profile.json');

        $this->assertSame(['dev.ucp.shopping.checkout', 'dev.ucp.shopping.catalog'], $names);
    }

    public function testHttpFailureReturnsEmptyAndLogsWarning(): void
    {
        $this->curl->method('get')->willThrowException(new \RuntimeException('Connection refused'));
        $this->logger->expects($this->once())->method('warning')->with(
            'UCP: failed to fetch platform profile',
            $this->arrayHasKey('uri')
        );

        $names = $this->fetcher->fetchCapabilityNames('https://example.com/profile.json');

        $this->assertSame([], $names);
    }

    public function testInProcessCacheSkipsSecondHttpCall(): void
    {
        $body = json_encode([
            'ucp' => ['capabilities' => ['dev.ucp.shopping.checkout' => []]]
        ]);
        $this->curl->method('getBody')->willReturn($body);

        // Only one HTTP call expected despite two fetchCapabilityNames calls
        $this->curl->expects($this->once())->method('get');

        $this->fetcher->fetchCapabilityNames('https://example.com/profile.json');
        $this->fetcher->fetchCapabilityNames('https://example.com/profile.json');
    }
}
