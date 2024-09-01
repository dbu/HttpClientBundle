<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

@trigger_error('The '.__NAMESPACE__.'\PluginClientFactory class is deprecated since version 1.8 and will be removed in 2.0. Use Http\Client\Common\PluginClientFactory instead.', E_USER_DEPRECATED);

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;

/**
 * This factory creates a PluginClient.
 *
 * @deprecated
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class PluginClientFactory
{
    /**
     * @param Plugin[] $plugins
     * @param array    $config              config to the client factory
     * @param array    $pluginClientOptions config forwarded to the PluginClient
     *
     * @return PluginClient
     */
    public static function createPluginClient(array $plugins, ClientFactory|callable $factory, array $config, array $pluginClientOptions = [])
    {
        if ($factory instanceof ClientFactory) {
            $client = $factory->createClient($config);
        } elseif (is_callable($factory)) {
            $client = $factory($config);
        } else {
            throw new \RuntimeException(sprintf('Second argument to PluginClientFactory::createPluginClient must be a "%s" or a callable.', ClientFactory::class));
        }

        return new PluginClient($client, $plugins, $pluginClientOptions);
    }
}
