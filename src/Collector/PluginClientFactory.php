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
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    public function __construct(Collector $collector, Formatter $formatter, Stopwatch $stopwatch)
    {
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @param ClientInterface|HttpAsyncClient $client
     * @param Plugin[]                        $plugins
     * @param array                           $options {
     *
     *     @type string $client_name to give client a name which may be used when displaying client information like in
     *         the HTTPlugBundle profiler.
     * }
     *
     * @see PluginClient constructor for PluginClient specific $options.
     *
     * @return PluginClient
     */
    public function createClient($client, array $plugins = [], array $options = [])
    {
        $plugins = array_map(function (Plugin $plugin) {
            return new ProfilePlugin($plugin, $this->collector, $this->formatter);
        }, $plugins);

        $clientName = isset($options['client_name']) ? $options['client_name'] : 'Default';
        array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, $clientName));
        unset($options['client_name']);

        if (!$client instanceof ProfileClient) {
            $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
        }

        return new PluginClient($client, $plugins, $options);
    }
}
