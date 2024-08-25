<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Socket\Client;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SocketFactory implements ClientFactory
{
    public function createClient(array $config = [])
    {
        if (!class_exists('Http\Client\Socket\Client')) {
            throw new \LogicException('To use the Socket client you need to install the "php-http/socket-client" package.');
        }

        return new Client($config);
    }
}
