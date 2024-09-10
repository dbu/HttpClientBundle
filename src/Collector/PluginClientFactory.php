<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Client\HttpAsyncClient;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * This factory is used as a replacement for Http\Client\Common\PluginClientFactory when profiling is enabled. It
 * creates PluginClient instances with all profiling decorators and extra plugins.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class PluginClientFactory
{
    public function __construct(
        private readonly Collector $collector,
        private readonly Formatter $formatter,
        private readonly Stopwatch $stopwatch,
    ) {
    }

    /**
     * @param Plugin[]                    $plugins
     * @param array{client_name?: string} $options
     *
     * Options:
     * - client_name: to give client a name which may be used when displaying client information like in the HTTPlugBundle profiler
     *
     * @see PluginClient constructor for PluginClient specific $options.
     */
    public function createClient(HttpAsyncClient|ClientInterface $client, array $plugins = [], array $options = []): PluginClient
    {
        $plugins = array_map(fn (Plugin $plugin) => new ProfilePlugin($plugin, $this->collector, $this->formatter), $plugins);

        $clientName = $options['client_name'] ?? 'Default';
        array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, $clientName));
        unset($options['client_name']);

        if (!$client instanceof ProfileClient) {
            $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
        }

        return new PluginClient($client, $plugins, $options);
    }
}
