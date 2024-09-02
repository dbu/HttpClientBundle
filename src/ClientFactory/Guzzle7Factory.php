<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Adapter\Guzzle7\Client;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Guzzle7Factory implements ClientFactory
{
    public function createClient(array $config = [])
    {
        if (!class_exists('Http\Adapter\Guzzle7\Client')) {
            throw new \LogicException('To use the Guzzle7 adapter you need to install the "php-http/guzzle7-adapter" package.');
        }

        return Client::createWithConfig($config);
    }
}
