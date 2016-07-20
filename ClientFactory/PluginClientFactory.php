<?php

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;

/**
 * This factory creates a PluginClient.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class PluginClientFactory
{
    /**
     * @param Plugin[]               $plugins
     * @param ClientFactory|callable $factory
     * @param array                  $config              config to the client factory
     * @param array                  $pluginClientOptions config forwarded to the PluginClient
     *
     * @return PluginClient
     */
    public static function createPluginClient(array $plugins, $factory, array $config, array $pluginClientOptions = [])
    {
        if ($factory instanceof ClientFactory) {
            return new PluginClient($factory->createClient($config), $plugins, $pluginClientOptions);
        } elseif (is_callable($factory)) {
            return new PluginClient($factory($config), $plugins, $pluginClientOptions);
        }

        throw new \RuntimeException(sprintf('Second argument to PluginClientFactory::createPluginClient must be a "%s" or a callale.', ClientFactory::class));
    }
}
