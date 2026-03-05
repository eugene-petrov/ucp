<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Tests\Unit\Model;

use Aeqet\Ucp\Model\OpenApiSchemaService;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest as RestController;
use Magento\Webapi\Model\Rest\Swagger\Generator as SwaggerGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OpenApiSchemaServiceTest extends TestCase
{
    private SwaggerGenerator&MockObject $swaggerGenerator;
    private Request&MockObject $request;
    private OpenApiSchemaService $management;

    protected function setUp(): void
    {
        $this->swaggerGenerator = $this->createMock(SwaggerGenerator::class);
        $this->request = $this->createMock(Request::class);

        $this->management = new OpenApiSchemaService(
            $this->swaggerGenerator,
            $this->request,
        );
    }

    public function testGetOpenApiSchemaFiltersOnlyUcpServices(): void
    {
        $allServices = [
            'aeqetUcpCheckoutSessionServiceV1',
            'aeqetUcpCartServiceV1',
            'magentoCustomerAccountManagementV1',
            'magentoCatalogProductRepositoryV1',
        ];

        $this->swaggerGenerator->method('getListOfServices')->willReturn($allServices);
        $this->stubRequest();

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                [
                    'aeqetUcpCheckoutSessionServiceV1',
                    'aeqetUcpCartServiceV1',
                ],
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaExcludesSchemaEndpointItself(): void
    {
        $allServices = [
            'aeqetUcpCheckoutSessionServiceV1',
            'aeqetUcpOpenApiSchemaServiceV1',
        ];

        $this->swaggerGenerator->method('getListOfServices')->willReturn($allServices);
        $this->stubRequest();

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                ['aeqetUcpCheckoutSessionServiceV1'],
                $this->anything(),
                $this->anything(),
                $this->anything()
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaReturnsRawJsonString(): void
    {
        $schemaJson = '{"swagger":"2.0","info":{"title":"UCP API"},"paths":{}}';

        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn(['aeqetUcpCheckoutSessionServiceV1']);
        $this->swaggerGenerator->method('generate')->willReturn($schemaJson);
        $this->stubRequest();

        $result = $this->management->getOpenApiSchema();

        $this->assertSame($schemaJson, $result);
    }

    public function testGetOpenApiSchemaBuildsCorrectSchemaEndpointUrl(): void
    {
        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn(['aeqetUcpCheckoutSessionServiceV1']);
        $this->request->method('getScheme')->willReturn('https');
        $this->request->method('getHttpHost')->willReturn('example.com');
        $this->request->method('getBaseUrl')->willReturn('');

        $expectedEndpointUrl = '/rest/V1' . RestController::SCHEMA_PATH;

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $expectedEndpointUrl
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaBuildsCorrectSchemaEndpointUrlForSubdirectoryInstall(): void
    {
        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn(['aeqetUcpCheckoutSessionServiceV1']);
        $this->request->method('getScheme')->willReturn('https');
        $this->request->method('getHttpHost')->willReturn('example.com');
        $this->request->method('getBaseUrl')->willReturn('/magento/');

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                '/magento/rest/V1' . RestController::SCHEMA_PATH
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaPassesHostWithPort(): void
    {
        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn(['aeqetUcpCheckoutSessionServiceV1']);
        $this->request->method('getScheme')->willReturn('https');
        $this->request->method('getBaseUrl')->willReturn('');
        $this->request->expects($this->once())
            ->method('getHttpHost')
            ->with(false)
            ->willReturn('example.com:8443');

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->anything(),
                $this->anything(),
                'example.com:8443',
                $this->anything()
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaFallsBackToEmptyHostWhenHttpHostMissing(): void
    {
        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn(['aeqetUcpCheckoutSessionServiceV1']);
        $this->request->method('getScheme')->willReturn('https');
        $this->request->method('getBaseUrl')->willReturn('');
        $this->request->method('getHttpHost')->willReturn(false);

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with(
                $this->anything(),
                $this->anything(),
                '',
                $this->anything()
            )
            ->willReturn('{"swagger":"2.0"}');

        $this->management->getOpenApiSchema();
    }

    public function testGetOpenApiSchemaWithNoUcpServicesPassesEmptyArray(): void
    {
        $this->swaggerGenerator->method('getListOfServices')
            ->willReturn([
                'magentoCustomerAccountManagementV1',
                'magentoCatalogProductRepositoryV1',
            ]);
        $this->stubRequest();

        $this->swaggerGenerator->expects($this->once())
            ->method('generate')
            ->with([], $this->anything(), $this->anything(), $this->anything())
            ->willReturn('{"swagger":"2.0","paths":{}}');

        $result = $this->management->getOpenApiSchema();
        $this->assertSame('{"swagger":"2.0","paths":{}}', $result);
    }

    // ----- helpers -----

    private function stubRequest(): void
    {
        $this->request->method('getScheme')->willReturn('https');
        $this->request->method('getHttpHost')->willReturn('example.com');
        $this->request->method('getBaseUrl')->willReturn('');
    }
}
