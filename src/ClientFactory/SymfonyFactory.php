<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class SymfonyFactory implements ClientFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function createClient(array $config = [])
    {
        if (!class_exists(HttplugClient::class)) {
            throw new \LogicException('To use the Symfony client you need to install the "symfony/http-client" package.');
        }

        return new HttplugClient(HttpClient::create($config), $this->responseFactory, $this->streamFactory);
    }
}
