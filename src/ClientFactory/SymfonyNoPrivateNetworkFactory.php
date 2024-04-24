<?php

namespace Http\HttplugBundle\ClientFactory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;

class SymfonyNoPrivateNetworkFactory
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    public function __construct(ResponseFactoryInterface $responseFactory, StreamFactoryInterface $streamFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists(HttplugClient::class)) {
            throw new \LogicException('To use the Symfony client you need to install the "symfony/http-client" package.');
        }

        if (!class_exists(NoPrivateNetworkHttpClient::class)) {
            throw new \LogicException('To use the No Private Network client you need to install the "symfony/http-client" version 5.1 or greater.');
        }

        return new HttplugClient(new NoPrivateNetworkHttpClient(HttpClient::create($config)), $this->responseFactory, $this->streamFactory);
    }
}
