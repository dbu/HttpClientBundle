<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Adapter\Guzzle7\Client;
use Psr\Http\Client\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Guzzle7Factory implements ClientFactory
{
    public function createClient(array $config = []): ClientInterface
    {
        if (!class_exists('Http\Adapter\Guzzle7\Client')) {
            throw new \LogicException('To use the Guzzle7 adapter you need to install the "php-http/guzzle7-adapter" package.');
        }

        return Client::createWithConfig($config);
    }
}
