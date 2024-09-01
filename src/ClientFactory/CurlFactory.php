<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Curl\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class CurlFactory implements ClientFactory
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
        if (!class_exists('Http\Client\Curl\Client')) {
            throw new \LogicException('To use the Curl client you need to install the "php-http/curl-client" package.');
        }

        // Try to resolve curl constant names
        foreach ($config as $key => $value) {
            // If the $key is a string we assume it is a constant
            if (is_string($key)) {
                if (null === ($constantValue = constant($key))) {
                    throw new \LogicException(sprintf('Key %s is not an int nor a CURL constant', $key));
                }

                unset($config[$key]);
                $config[$constantValue] = $value;
            }
        }

        return new Client($this->responseFactory, $this->streamFactory, $config);
    }
}
