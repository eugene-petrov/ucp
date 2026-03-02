<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

interface OpenApiSchemaManagementInterface
{
    /**
     * Get the API schema (Swagger 2.0 / OpenAPI 2.0) for all UCP endpoints.
     *
     * Note: returns Swagger 2.0 format, not OpenAPI 3.0.
     * Returns raw JSON string so Magento's REST framework passes it through
     * without re-serialization (Response::_render skips encoding for strings).
     *
     * @return string
     */
    public function getOpenApiSchema(): string;
}
