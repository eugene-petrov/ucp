<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model\Webhook;

use Aeqet\Ucp\Api\Data\Webhook\DeliveryInterface;
use Aeqet\Ucp\Api\Webhook\SignerInterface;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery as WebhookDeliveryResource;
use Aeqet\Ucp\Model\ResourceModel\WebhookDelivery\CollectionFactory as WebhookDeliveryCollectionFactory;
use Aeqet\Ucp\Model\Webhook\DeliveryEntity;
use Aeqet\Ucp\Model\Webhook\DeliveryProcessor;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\ClientFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class DeliveryProcessorTest extends TestCase
{
    private WebhookDeliveryCollectionFactory&MockObject $deliveryCollectionFactory;
    private WebhookDeliveryResource&MockObject $deliveryResource;
    private SignerInterface&MockObject $signer;
    private ClientFactory&MockObject $clientFactory;
    private Curl&MockObject $httpClient;
    private LoggerInterface&MockObject $logger;
    private DeliveryProcessor $processor;

    protected function setUp(): void
    {
        $this->deliveryCollectionFactory = $this->createMock(WebhookDeliveryCollectionFactory::class);
        $this->deliveryResource = $this->createMock(WebhookDeliveryResource::class);
        $this->signer = $this->createMock(SignerInterface::class);
        $this->clientFactory = $this->createMock(ClientFactory::class);
        $this->httpClient = $this->getMockBuilder(Curl::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setTimeout', 'addHeader', 'post', 'getStatus'])
            ->getMock();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->clientFactory->method('create')->willReturn($this->httpClient);

        $this->processor = new DeliveryProcessor(
            $this->deliveryCollectionFactory,
            $this->deliveryResource,
            $this->signer,
            $this->clientFactory,
            $this->logger
        );
    }

    public function testProcess_successfulDelivery_marksDelivered(): void
    {
        $delivery = $this->makeDelivery('whdlv_1', 'https://example.com/hook', '{}', 0);

        $this->signer->method('getSignatureHeader')->willReturn('sig1=:abc:');
        $this->httpClient->method('getStatus')->willReturn(200);

        $delivery->expects($this->once())->method('setStatus')->with(DeliveryInterface::STATUS_DELIVERED)->willReturnSelf();
        $delivery->expects($this->once())->method('setLastError')->with(null)->willReturnSelf();
        $delivery->expects($this->once())->method('setNextRetryAt')->with(null)->willReturnSelf();
        $this->deliveryResource->expects($this->once())->method('save')->with($delivery);

        $this->processor->processDelivery($delivery);
    }

    public function testProcess_http404_marksAsPermanentFailure(): void
    {
        $delivery = $this->makeDelivery('whdlv_2', 'https://example.com/hook', '{}', 0);

        $this->signer->method('getSignatureHeader')->willReturn('sig1=:abc:');
        $this->httpClient->method('getStatus')->willReturn(404);

        $delivery->expects($this->once())->method('setStatus')->with(DeliveryInterface::STATUS_FAILED)->willReturnSelf();
        $delivery->expects($this->once())->method('setLastError')
            ->with('HTTP 404 (client error, no retry)')->willReturnSelf();
        $delivery->expects($this->never())->method('setNextRetryAt');
        $this->deliveryResource->expects($this->once())->method('save');
        $this->logger->expects($this->once())->method('warning');

        $this->processor->processDelivery($delivery);
    }

    /**
     * 429 Too Many Requests must still be retried (rate-limited, not a permanent error).
     */
    public function testProcess_http429_schedulesRetry(): void
    {
        $delivery = $this->makeDelivery('whdlv_2b', 'https://example.com/hook', '{}', 0);

        $this->signer->method('getSignatureHeader')->willReturn('sig1=:abc:');
        $this->httpClient->method('getStatus')->willReturn(429);

        $delivery->expects($this->never())->method('setStatus');
        $delivery->expects($this->once())->method('setLastError')->with('HTTP 429')->willReturnSelf();
        $delivery->expects($this->once())->method('setNextRetryAt')
            ->with($this->callback(function (?string $val): bool {
                if ($val === null) {
                    return false;
                }
                // DeliveryProcessor stores UTC via gmdate(); parse explicitly as UTC for comparison.
                $ts = strtotime($val . ' UTC');
                $expected = time() + 60;
                return abs($ts - $expected) <= 2;
            }))
            ->willReturnSelf();
        $this->deliveryResource->expects($this->once())->method('save');

        $this->processor->processDelivery($delivery);
    }

    public function testProcess_maxAttemptsReached_marksFailed(): void
    {
        $delivery = $this->makeDelivery('whdlv_3', 'https://example.com/hook', '{}', 5);

        $this->signer->method('getSignatureHeader')->willReturn('sig1=:abc:');
        $this->httpClient->method('getStatus')->willReturn(500);

        $delivery->expects($this->once())->method('setStatus')->with(DeliveryInterface::STATUS_FAILED)->willReturnSelf();
        $delivery->expects($this->once())->method('setLastError')->with('HTTP 500')->willReturnSelf();
        $this->deliveryResource->expects($this->once())->method('save');
        $this->logger->expects($this->once())->method('warning');

        $this->processor->processDelivery($delivery);
    }

    public function testProcess_signerThrows_schedulesRetry(): void
    {
        $delivery = $this->makeDelivery('whdlv_5', 'https://example.com/hook', '{}', 0);

        $this->signer->method('getSignatureHeader')->willThrowException(new RuntimeException('No signing key'));

        $delivery->expects($this->never())->method('setStatus');
        $delivery->expects($this->once())->method('setLastError')->with('No signing key')->willReturnSelf();
        $delivery->expects($this->once())->method('setNextRetryAt')->willReturnSelf();
        $this->deliveryResource->expects($this->once())->method('save');

        $this->processor->processDelivery($delivery);
    }

    private function makeDelivery(
        string $deliveryId,
        string $targetUrl,
        string $payload,
        int $attempts
    ): DeliveryEntity&MockObject {
        $delivery = $this->getMockBuilder(DeliveryEntity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData', 'getAttempts', 'setAttempts', 'setStatus', 'setLastError', 'setNextRetryAt'])
            ->getMock();

        $delivery->method('getData')->willReturnCallback(
            function (string $key) use ($deliveryId, $targetUrl, $payload) {
                return match ($key) {
                    'delivery_id' => $deliveryId,
                    'target_url'  => $targetUrl,
                    'payload'     => $payload,
                    default       => null,
                };
            }
        );
        $delivery->method('getAttempts')->willReturn($attempts);
        $delivery->method('setAttempts')->willReturnSelf();

        return $delivery;
    }
}
