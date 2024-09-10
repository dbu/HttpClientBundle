<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;

/**
 * Use auto discovery to find a HTTP client.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class AutoDiscoveryFactory implements ClientFactory
{
    public function createClient(array $config = []): ClientInterface
    {
        return Psr18ClientDiscovery::find();
    }
}
