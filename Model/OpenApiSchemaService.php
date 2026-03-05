<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Model;

use Aeqet\Ucp\Api\OpenApiSchemaServiceInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Webapi\Controller\Rest as RestController;
use Magento\Webapi\Model\Rest\Swagger\Generator as SwaggerGenerator;

class OpenApiSchemaService implements OpenApiSchemaServiceInterface
{
    /**
     * Constructor.
     *
     * @param SwaggerGenerator $swaggerGenerator
     * @param Request $request
     */
    public function __construct(
        private readonly SwaggerGenerator $swaggerGenerator,
        private readonly Request $request,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getOpenApiSchema(): string
    {
        return $this->swaggerGenerator->generate(
            $this->getUcpServiceNames(),
            $this->request->getScheme(),
            $this->request->getHttpHost(false) ?: '',
            $this->buildSchemaEndpointUrl()
        );
    }

    /**
     * Retrieve ucp services
     *
     * @return string[]
     */
    private function getUcpServiceNames(): array
    {
        return array_values(array_filter(
            $this->swaggerGenerator->getListOfServices(),
            static fn(string $name): bool => str_starts_with($name, 'aeqetUcp')
                && $name !== 'aeqetUcpOpenApiSchemaServiceV1'
        ));
    }

    /**
     * Build the endpoint URL used by SwaggerGenerator to derive basePath.
     *
     * SwaggerGenerator extracts basePath as everything before the '/schema'
     * marker in the endpoint URL (see AbstractSchemaGenerator::generateSchema):
     *   strstr($endpointUrl, '/schema', true)  →  e.g. '/rest/V1'
     *
     * We construct a canonical path containing that marker so the generated
     * schema has a correct Swagger 2.0 basePath regardless of our actual URL.
     */
    private function buildSchemaEndpointUrl(): string
    {
        return rtrim($this->request->getBaseUrl(), '/') . '/rest/V1' . RestController::SCHEMA_PATH;
    }
}
