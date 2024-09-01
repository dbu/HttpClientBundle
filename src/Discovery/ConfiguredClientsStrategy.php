<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Discovery;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Strategy\DiscoveryStrategy;

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
    /**
     * @var HttpClient
     */
    private static $client;

    /**
     * @var HttpAsyncClient
     */
    private static $asyncClient;

    /**
     * @param HttpClient      $httpClient
     * @param HttpAsyncClient $asyncClient
     */
    public function __construct(HttpClient $httpClient = null, HttpAsyncClient $asyncClient = null)
    {
        self::$client = $httpClient;
        self::$asyncClient = $asyncClient;
        HttpClientDiscovery::clearCache();
    }

    /**
     * {@inheritdoc}
     */
    public static function getCandidates($type)
    {
        if (HttpClient::class === $type && null !== self::$client) {
            return [['class' => function () {
                return self::$client;
            }]];
        }

        if (HttpAsyncClient::class === $type && null !== self::$asyncClient) {
            return [['class' => function () {
                return self::$asyncClient;
            }]];
        }

        return [];
    }
}
