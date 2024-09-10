<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Adapter\React\Client;
use Psr\Http\Client\ClientInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ReactFactory implements ClientFactory
{
    public function createClient(array $config = []): ClientInterface
    {
        if (!class_exists('Http\Adapter\React\Client')) {
            throw new \LogicException('To use the React adapter you need to install the "php-http/react-adapter" package.');
        }

        return new Client();
    }
}
