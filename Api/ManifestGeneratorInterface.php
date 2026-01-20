<?php

declare(strict_types=1);

namespace Aeqet\Ucp\Api;

interface ManifestGeneratorInterface
{
    /**
     * Generate UCP manifest data
     *
     * @return array<string, array>
     */
    public function generate(): array;
}
