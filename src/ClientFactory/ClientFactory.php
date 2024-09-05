<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Psr\Http\Client\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface ClientFactory
{
    /**
     * Input an array of configuration to be able to create a ClientInterface.
     */
    public function createClient(array $config = []): ClientInterface;
}
