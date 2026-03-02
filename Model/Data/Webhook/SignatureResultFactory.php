<?php
/**
 * UCP Webhook Signature Result Factory — source stub
 *
 * At runtime (after bin/magento setup:di:compile) this file is shadowed
 * by the auto-generated factory in generated/code/.  In unit tests (no
 * di:compile) Composer's PSR-4 autoloader picks up this file instead,
 * making the class available for PHPUnit mocking.
 */

declare(strict_types=1);

namespace Aeqet\Ucp\Model\Data\Webhook;

class SignatureResultFactory
{
    /**
     * Create a new SignatureResult instance
     *
     * @param array $data
     * @return SignatureResult
     */
    public function create(array $data = []): SignatureResult
    {
        return new SignatureResult($data);
    }
}
