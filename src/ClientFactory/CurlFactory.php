<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Curl\Client;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CurlFactory implements ClientFactory
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory
    ) {
    }

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
