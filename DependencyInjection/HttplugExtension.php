<?php

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Common\BatchClient;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\HttpClientDiscovery;
use Http\HttplugBundle\ClientFactory\DummyClient;
use Http\HttplugBundle\ClientFactory\PluginClientFactory;
use Http\HttplugBundle\Collector\DebugPlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Wsse;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author David Buchmann <mail@davidbu.ch>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');
        $loader->load('plugins.xml');

        // Register default services
        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        // Set main aliases
        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), $id);
        }

        // Configure toolbar
        if ($this->isConfigEnabled($container, $config['profiling'])) {
            $loader->load('data-collector.xml');

            if (!empty($config['profiling']['formatter'])) {
                // Add custom formatter
                $container
                    ->getDefinition('httplug.collector.debug_collector')
                    ->replaceArgument(0, new Reference($config['profiling']['formatter']))
                ;
            }

            $container
                ->getDefinition('httplug.formatter.full_http_message')
                ->addArgument($config['profiling']['captured_body_length'])
            ;
        }

        $this->configureClients($container, $config);
        $this->configureSharedPlugins($container, $config['plugins']); // must be after clients, as clients.X.plugins might use plugins as templates that will be removed
        $this->configureAutoDiscoveryClients($container, $config);
    }

    /**
     * Configure client services.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureClients(ContainerBuilder $container, array $config)
    {
        $first = null;

        foreach ($config['clients'] as $name => $arguments) {
            if ($first === null) {
                // Save the name of the first configured client.
                $first = $name;
            }

            $this->configureClient($container, $name, $arguments, $this->isConfigEnabled($container, $config['profiling']));
        }

        // If we have clients configured
        if ($first !== null) {
            // If we do not have a client named 'default'
            if (!isset($config['clients']['default'])) {
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', 'httplug.client.'.$first);
            }
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureSharedPlugins(ContainerBuilder $container, array $config)
    {
        if (!empty($config['authentication'])) {
            $this->configureAuthentication($container, $config['authentication']);
        }
        unset($config['authentication']);

        foreach ($config as $name => $pluginConfig) {
            $pluginId = 'httplug.plugin.'.$name;

            if ($this->isConfigEnabled($container, $pluginConfig)) {
                $def = $container->getDefinition($pluginId);
                $this->configurePluginByName($name, $def, $pluginConfig, $container, $pluginId);
            } else {
                $container->removeDefinition($pluginId);
            }
        }
    }

    /**
     * @param string           $name
     * @param Definition       $definition
     * @param array            $config
     * @param ContainerBuilder $container  In case we need to add additional services for this plugin
     * @param string           $serviceId  Service id of the plugin, in case we need to add additional services for this plugin.
     */
    private function configurePluginByName($name, Definition $definition, array $config, ContainerInterface $container, $serviceId)
    {
        switch ($name) {
            case 'cache':
                $definition
                    ->replaceArgument(0, new Reference($config['cache_pool']))
                    ->replaceArgument(1, new Reference($config['stream_factory']))
                    ->replaceArgument(2, $config['config']);
                break;
            case 'cookie':
                $definition->replaceArgument(0, new Reference($config['cookie_jar']));
                break;
            case 'decoder':
                $definition->addArgument([
                    'use_content_encoding' => $config['use_content_encoding'],
                ]);
                break;
            case 'history':
                $definition->replaceArgument(0, new Reference($config['journal']));
                break;
            case 'logger':
                $definition->replaceArgument(0, new Reference($config['logger']));
                if (!empty($config['formatter'])) {
                    $definition->replaceArgument(1, new Reference($config['formatter']));
                }
                break;
            case 'redirect':
                $definition->addArgument([
                    'preserve_header' => $config['preserve_header'],
                    'use_default_for_multiple' => $config['use_default_for_multiple'],
                ]);
                break;
            case 'retry':
                $definition->addArgument([
                    'retries' => $config['retry'],
                ]);
                break;
            case 'stopwatch':
                $definition->replaceArgument(0, new Reference($config['stopwatch']));
                break;

            /* client specific plugins */

            case 'add_host':
                $uriService = $serviceId.'.host_uri';
                $this->createUri($container, $uriService, $config['host']);
                $definition->replaceArgument(0, new Reference($uriService));
                $definition->replaceArgument(1, [
                    'replace' => $config['replace'],
                ]);
                break;
            case 'header_append':
            case 'header_defaults':
            case 'header_set':
            case 'header_remove':
                $definition->replaceArgument(0, $config['headers']);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Internal exception: Plugin %s is not handled', $name));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return array List of service ids for the authentication plugins.
     */
    private function configureAuthentication(ContainerBuilder $container, array $config, $servicePrefix = 'httplug.plugin.authentication')
    {
        $pluginServices = [];

        foreach ($config as $name => $values) {
            $authServiceKey = sprintf($servicePrefix.'.%s.auth', $name);
            switch ($values['type']) {
                case 'bearer':
                    $container->register($authServiceKey, Bearer::class)
                        ->addArgument($values['token']);
                    break;
                case 'basic':
                    $container->register($authServiceKey, BasicAuth::class)
                        ->addArgument($values['username'])
                        ->addArgument($values['password']);
                    break;
                case 'wsse':
                    $container->register($authServiceKey, Wsse::class)
                        ->addArgument($values['username'])
                        ->addArgument($values['password']);
                    break;
                case 'service':
                    $authServiceKey = $values['service'];
                    break;
                default:
                    throw new \LogicException(sprintf('Unknown authentication type: "%s"', $values['type']));
            }

            $pluginServiceKey = $servicePrefix.'.'.$name;
            $container->register($pluginServiceKey, AuthenticationPlugin::class)
                ->addArgument(new Reference($authServiceKey))
            ;
            $pluginServices[] = $pluginServiceKey;
        }

        return $pluginServices;
    }

    /**
     * @param ContainerBuilder $container
     * @param string           $clientName
     * @param array            $arguments
     * @param bool             $profiling
     */
    private function configureClient(ContainerBuilder $container, $clientName, array $arguments, $profiling)
    {
        $serviceId = 'httplug.client.'.$clientName;

        $plugins = [];
        foreach ($arguments['plugins'] as $plugin) {
            list($pluginName, $pluginConfig) = each($plugin);
            if ('reference' === $pluginName) {
                $plugins[] = $pluginConfig['id'];
            } elseif ('authentication' === $pluginName) {
                $plugins = array_merge($plugins, $this->configureAuthentication($container, $pluginConfig, $serviceId.'.authentication'));
            } else {
                $pluginServiceId = $serviceId.'.plugin.'.$pluginName;
                $def = clone $container->getDefinition('httplug.plugin'.'.'.$pluginName);
                $def->setAbstract(false);
                $this->configurePluginByName($pluginName, $def, $pluginConfig, $container, $pluginServiceId);
                $container->setDefinition($pluginServiceId, $def);
                $plugins[] = $pluginServiceId;
            }
        }

        $pluginClientOptions = [];
        if ($profiling) {
            // Add the stopwatch plugin
            if (!in_array('httplug.plugin.stopwatch', $arguments['plugins'])) {
                array_unshift($plugins, 'httplug.plugin.stopwatch');
            }

            // Tell the plugin journal what plugins we used
            $container
                ->getDefinition('httplug.collector.plugin_journal')
                ->addMethodCall('setPlugins', [$clientName, $plugins])
            ;

            $debugPluginServiceId = $this->registerDebugPlugin($container, $serviceId);

            $pluginClientOptions['debug_plugins'] = [new Reference($debugPluginServiceId)];
        }

        $container
            ->register($serviceId, DummyClient::class)
            ->setFactory([PluginClientFactory::class, 'createPluginClient'])
            ->addArgument(
                array_map(
                    function ($id) {
                        return new Reference($id);
                    },
                    $plugins
                )
            )
            ->addArgument(new Reference($arguments['factory']))
            ->addArgument($arguments['config'])
            ->addArgument($pluginClientOptions)
        ;

        /*
         * Decorate the client with clients from client-common
         */
        if ($arguments['flexible_client']) {
            $container
                ->register($serviceId.'.flexible', FlexibleHttpClient::class)
                ->addArgument(new Reference($serviceId.'.flexible.inner'))
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['http_methods_client']) {
            $container
                ->register($serviceId.'.http_methods', HttpMethodsClient::class)
                ->setArguments([new Reference($serviceId.'.http_methods.inner'), new Reference('httplug.message_factory')])
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['batch_client']) {
            $container
                ->register($serviceId.'.batch_client', BatchClient::class)
                ->setArguments([new Reference($serviceId.'.batch_client.inner')])
                ->setPublic(false)
                ->setDecoratedService($serviceId)
            ;
        }
    }

    /**
     * Create a URI object with the default URI factory.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId Name of the private service to create
     * @param string           $uri       String representation of the URI
     */
    private function createUri(ContainerBuilder $container, $serviceId, $uri)
    {
        $container
            ->register($serviceId, UriInterface::class)
            ->setPublic(false)
            ->setFactory([new Reference('httplug.uri_factory'), 'createUri'])
            ->addArgument($uri)
        ;
    }

    /**
     * Make the user can select what client is used for auto discovery. If none is provided, a service will be created
     * by finding a client using auto discovery.
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    private function configureAutoDiscoveryClients(ContainerBuilder $container, array $config)
    {
        $httpClient = $config['discovery']['client'];

        if (!empty($httpClient)) {
            if ($httpClient === 'auto') {
                $httpClient = $this->registerAutoDiscoverableClient(
                    $container,
                    'auto_discovered_client',
                    [HttpClientDiscovery::class, 'find'],
                    $this->isConfigEnabled($container, $config['profiling'])
                );
            }

            $httpClient = new Reference($httpClient);
        }

        $asyncHttpClient = $config['discovery']['async_client'];

        if (!empty($asyncHttpClient)) {
            if ($asyncHttpClient === 'auto') {
                $asyncHttpClient = $this->registerAutoDiscoverableClient(
                    $container,
                    'auto_discovered_async',
                    [HttpAsyncClientDiscovery::class, 'find'],
                    $this->isConfigEnabled($container, $config['profiling'])
                );
            }

            $asyncHttpClient = new Reference($asyncHttpClient);
        }

        $container
            ->getDefinition('httplug.strategy')
            ->addArgument($httpClient)
            ->addArgument($asyncHttpClient)
        ;
    }

    /**
     * Find a client with auto discovery and return a service Reference to it.
     *
     * @param ContainerBuilder $container
     * @param string           $name
     * @param callable         $factory
     * @param bool             $profiling
     *
     * @return string service id
     */
    private function registerAutoDiscoverableClient(ContainerBuilder $container, $name, $factory, $profiling)
    {
        $serviceId = 'httplug.auto_discovery.'.$name;

        $pluginClientOptions = [];

        if ($profiling) {
            // Tell the plugin journal what plugins we used
            $container
                ->getDefinition('httplug.collector.plugin_journal')
                ->addMethodCall('setPlugins', [$name, ['httplug.plugin.stopwatch']])
            ;

            $debugPluginServiceId = $this->registerDebugPlugin($container, $serviceId);

            $pluginClientOptions['debug_plugins'] = [new Reference($debugPluginServiceId)];
        }

        $container
            ->register($serviceId, DummyClient::class)
            ->setFactory([PluginClientFactory::class, 'createPluginClient'])
            ->setArguments([[new Reference('httplug.plugin.stopwatch')], $factory, [], $pluginClientOptions])
        ;

        return $serviceId;
    }

    /**
     * Create a new plugin service for this client.
     *
     * @param ContainerBuilder $container
     * @param string           $serviceId
     *
     * @return string
     */
    private function registerDebugPlugin(ContainerBuilder $container, $serviceId)
    {
        $serviceIdDebugPlugin = $serviceId.'.debug_plugin';

        $container
            ->register($serviceIdDebugPlugin, DebugPlugin::class)
            ->addArgument(new Reference('httplug.collector.debug_collector'))
            ->addArgument(substr($serviceId, strrpos($serviceId, '.') + 1))
            ->setPublic(false)
        ;

        return $serviceIdDebugPlugin;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }
}
