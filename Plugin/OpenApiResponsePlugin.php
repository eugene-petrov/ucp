<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Plugin;

use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\Webapi\Rest\Response;

/**
 * Bypasses Magento's JSON renderer for the openapi.json endpoint.
 *
 * The REST response renderer always calls json_encode() on return values,
 * which double-encodes a string that is already valid JSON.
 * This plugin writes the raw JSON body directly when the request targets
 * the UCP OpenAPI schema endpoint.
 */
class OpenApiResponsePlugin
{
    private const OPENAPI_PATH = '/V1/ucp/openapi.json';

    /**
     * Constructor.
     *
     * @param Request $request
     */
    public function __construct(
        private readonly Request $request,
    ) {
    }

    /**
     * Set header Content-Type: application/json
     *
     * @param Response $subject
     * @param callable $proceed
     * @param mixed $outputData
     * @return void
     */
    public function aroundPrepareResponse(
        Response $subject,
        callable $proceed,
        mixed $outputData = null,
    ): void {
        if (!is_string($outputData) || $this->request->getPathInfo() !== self::OPENAPI_PATH) {
            $proceed($outputData);
            return;
        }

        $subject->setBody($outputData);
        $subject->setHeader('Content-Type', 'application/json', true);
    }
}
