<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Discovery;

use Http\Client\HttpAsyncClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;
use Psr\Http\Client\ClientInterface;

/**
 * A strategy that provide clients configured with HTTPlug bundle. With help from this strategy
 * we can use the web debug toolbar for clients found with the discovery.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class ConfiguredClientsStrategy implements DiscoveryStrategy
{
    private static ?ClientInterface $client = null;

    private static ?HttpAsyncClient $asyncClient = null;

    public function __construct(?ClientInterface $httpClient = null, ?HttpAsyncClient $asyncClient = null)
    {
        self::$client = $httpClient;
        self::$asyncClient = $asyncClient;
        Psr18ClientDiscovery::clearCache();
    }

    public static function getCandidates($type)
    {
        if (ClientInterface::class === $type && null !== self::$client) {
            return [['class' => fn () => self::$client]];
        }

        if (HttpAsyncClient::class === $type && null !== self::$asyncClient) {
            return [['class' => fn () => self::$asyncClient]];
        }

        return [];
    }
}
