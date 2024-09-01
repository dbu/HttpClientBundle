<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\HttpAsyncClient;
use Http\HttplugBundle\ClientFactory\ClientFactory;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * The ProfileClientFactory decorates any ClientFactory and returns the created client decorated by a ProfileClient.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class ProfileClientFactory implements ClientFactory
{
    /**
     * @var ClientFactory|callable
     */
    private $factory;

    /**
     * @param ClientFactory|callable $factory
     */
    public function __construct(
        $factory,
        private readonly Collector $collector,
        private readonly Formatter $formatter,
        private readonly Stopwatch $stopwatch
    ) {
        if (!$factory instanceof ClientFactory && !is_callable($factory)) {
            throw new \RuntimeException(sprintf('First argument to ProfileClientFactory::__construct must be a "%s" or a callable.', ClientFactory::class));
        }
        $this->factory = $factory;
    }

    public function createClient(array $config = [])
    {
        $client = is_callable($this->factory) ? call_user_func($this->factory, $config) : $this->factory->createClient($config);

        if (!($client instanceof ClientInterface && $client instanceof HttpAsyncClient)) {
            $client = new FlexibleHttpClient($client);
        }

        return new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
    }
}
