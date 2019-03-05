<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Discovery\Psr18ClientDiscovery;

/**
 * Use auto discovery to find a HTTP client.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class AutoDiscoveryFactory implements ClientFactory
{
    public function createClient(array $config = [])
    {
        return Psr18ClientDiscovery::find();
    }
}
