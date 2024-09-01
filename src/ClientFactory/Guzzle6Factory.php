<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Adapter\Guzzle6\Client;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class Guzzle6Factory implements ClientFactory
{
    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists('Http\Adapter\Guzzle6\Client')) {
            throw new \LogicException('To use the Guzzle6 adapter you need to install the "php-http/guzzle6-adapter" package.');
        }

        return Client::createWithConfig($config);
    }
}
