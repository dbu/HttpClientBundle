<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Socket\Client;
use Psr\Http\Client\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class SocketFactory implements ClientFactory
{
    public function createClient(array $config = []): ClientInterface
    {
        if (!class_exists('Http\Client\Socket\Client')) {
            throw new \LogicException('To use the Socket client you need to install the "php-http/socket-client" package.');
        }

        return new Client($config);
    }
}
