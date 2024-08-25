<?php

declare(strict_types=1);

namespace Http\HttplugBundle\DependencyInjection;

use Http\Client\Common\BatchClient;
use Http\Client\Common\BatchClientInterface;
use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\HttpMethodsClientInterface;
use Http\Client\Common\Plugin\AuthenticationPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientFactory;
use Http\Client\HttpAsyncClient;
use Http\Client\Plugin\Vcr\RecordPlugin;
use Http\Client\Plugin\Vcr\ReplayPlugin;
use Http\Message\Authentication\BasicAuth;
use Http\Message\Authentication\Bearer;
use Http\Message\Authentication\Header;
use Http\Message\Authentication\QueryParam;
use Http\Message\Authentication\Wsse;
use Http\Mock\Client as MockClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Environment as TwigEnvironment;

/**
 * @author David Buchmann <mail@davidbu.ch>
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class HttplugExtension extends Extension
{
    public const HTTPLUG_CLIENT_TAG = 'httplug.client';

    /**
     * Used to check is the VCR plugin is installed.
     */
    private bool $useVcrPlugin = false;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('services.xml');
        $loader->load('plugins.xml');
        if (\class_exists(MockClient::class)) {
            $loader->load('mock-client.xml');
        }

        // Register default services
        foreach ($config['classes'] as $service => $class) {
            if (!empty($class)) {
                $container->register(sprintf('httplug.%s.default', $service), $class);
            }
        }

        // Set main aliases
        foreach ($config['main_alias'] as $type => $id) {
            $container->setAlias(sprintf('httplug.%s', $type), new Alias($id, true));
        }

        // Configure toolbar
        $profilingEnabled = $this->isConfigEnabled($container, $config['profiling']);
        if ($profilingEnabled) {
            $loader->load('data-collector.xml');

            if (!empty($config['profiling']['formatter'])) {
                // Add custom formatter
                $container
                    ->getDefinition('httplug.collector.formatter')
                    ->replaceArgument(0, new Reference($config['profiling']['formatter']))
                ;
            }

            $container
                ->getDefinition('httplug.formatter.full_http_message')
                ->addArgument($config['profiling']['captured_body_length'])
            ;
            $container
                ->getDefinition('httplug.collector.collector')
                ->addArgument($config['profiling']['captured_body_length'])
            ;

            if (!class_exists(TwigEnvironment::class) && !class_exists(\Twig_Environment::class)) {
                $container->removeDefinition('httplug.collector.twig.http_message');
            }
        }

        $this->configureClients($container, $config);
        $this->configurePlugins($container, $config['plugins']); // must be after clients, as clients.X.plugins might use plugins as templates that will be removed
        $this->configureAutoDiscoveryClients($container, $config);

        if (!$config['default_client_autowiring']) {
            $container->removeAlias(HttpAsyncClient::class);
            $container->removeAlias(ClientInterface::class);
        }

        if ($this->useVcrPlugin) {
            if (!\class_exists(RecordPlugin::class)) {
                throw new InvalidConfigurationException('You need to require the VCR plugin to be able to use it: "composer require --dev php-http/vcr-plugin".');
            }

            $loader->load('vcr-plugin.xml');
        }
    }

    /**
     * Configure client services.
     */
    private function configureClients(ContainerBuilder $container, array $config): void
    {
        $first = null;
        $clients = [];

        foreach ($config['clients'] as $name => $arguments) {
            if (null === $first) {
                // Save the name of the first configured client.
                $first = $name;
            }

            $this->configureClient($container, $name, $arguments);
            $clients[] = $name;
        }

        // If we have clients configured
        if (null !== $first) {
            // If we do not have a client named 'default'
            if (!array_key_exists('default', $config['clients'])) {
                $serviceId = 'httplug.client.'.$first;
                // Alias the first client to httplug.client.default
                $container->setAlias('httplug.client.default', $serviceId);
                $default = $first;
            } else {
                $default = 'default';
                $serviceId = 'httplug.client.'.$default;
            }

            // Autowiring alias for special clients, if they are enabled on the default client
            if ($config['clients'][$default]['flexible_client']) {
                $container->setAlias(FlexibleHttpClient::class, $serviceId.'.flexible');
            }
            if ($config['clients'][$default]['http_methods_client']) {
                if (\interface_exists(HttpMethodsClientInterface::class)) {
                    // support for client-common 1.9
                    $container->setAlias(HttpMethodsClientInterface::class, $serviceId.'.http_methods');
                }
            }
            if ($config['clients'][$default]['batch_client']) {
                if (\interface_exists(BatchClientInterface::class)) {
                    // support for client-common 1.9
                    $container->setAlias(BatchClientInterface::class, $serviceId.'.batch_client');
                }
            }
        }
    }

    /**
     * Configure all Httplug plugins or remove their service definition.
     */
    private function configurePlugins(ContainerBuilder $container, array $config): void
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
            }
        }
    }

    /**
     * @param string           $name
     * @param ContainerBuilder $container In case we need to add additional services for this plugin
     * @param string           $serviceId service id of the plugin, in case we need to add additional services for this plugin
     */
    private function configurePluginByName(string $name, Definition $definition, array $config, ContainerBuilder $container, string $serviceId): void
    {
        switch ($name) {
            case 'cache':
                $options = $config['config'];
                if (\array_key_exists('respect_response_cache_directives', $options) && null === $options['respect_response_cache_directives']) {
                    unset($options['respect_response_cache_directives']);
                }
                if (!empty($options['cache_key_generator'])) {
                    $options['cache_key_generator'] = new Reference($options['cache_key_generator']);
                }

                if (empty($options['blacklisted_paths'])) {
                    unset($options['blacklisted_paths']);
                }

                $options['cache_listeners'] = array_map(fn(string $serviceName): Reference => new Reference($serviceName), $options['cache_listeners']);

                if (0 === count($options['cache_listeners'])) {
                    unset($options['cache_listeners']);
                }

                $definition
                    ->replaceArgument(0, new Reference($config['cache_pool']))
                    ->replaceArgument(1, new Reference($config['stream_factory']))
                    ->replaceArgument(2, $options)
                    ->setAbstract(false)
                ;

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
                $definition->setAbstract(false);

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
                $definition
                    ->replaceArgument(0, new Reference($config['stopwatch']))
                    ->setAbstract(false)
                ;

                break;

            case 'error':
                $definition->addArgument([
                    'only_server_exception' => $config['only_server_exception'],
                ]);

                break;

            /* client specific plugins */

            case 'add_host':
                $hostUriService = $serviceId.'.host_uri';
                $this->createUri($container, $hostUriService, $config['host']);
                $definition->replaceArgument(0, new Reference($hostUriService));
                $definition->replaceArgument(1, [
                    'replace' => $config['replace'],
                ]);

                break;

            case 'add_path':
                $pathUriService = $serviceId.'.path_uri';
                $this->createUri($container, $pathUriService, $config['path']);
                $definition->replaceArgument(0, new Reference($pathUriService));

                break;

            case 'base_uri':
                $baseUriService = $serviceId.'.base_uri';
                $this->createUri($container, $baseUriService, $config['uri']);
                $definition->replaceArgument(0, new Reference($baseUriService));
                $definition->replaceArgument(1, [
                    'replace' => $config['replace'],
                ]);

                break;

            case 'content_type':
                unset($config['enabled']);
                $definition->replaceArgument(0, $config);

                break;

            case 'header_append':
            case 'header_defaults':
            case 'header_set':
            case 'header_remove':
                $definition->replaceArgument(0, $config['headers']);

                break;

            case 'query_defaults':
                $definition->replaceArgument(0, $config['parameters']);

                break;

            case 'request_seekable_body':
            case 'response_seekable_body':
                $definition->replaceArgument(0, $config);
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Internal exception: Plugin %s is not handled', $name));
        }
    }

    /**
     * @return string[] list of service ids for the authentication plugins
     */
    private function configureAuthentication(ContainerBuilder $container, array $config, $servicePrefix = 'httplug.plugin.authentication'): array
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
                case 'query_param':
                    $container->register($authServiceKey, QueryParam::class)
                        ->addArgument($values['params']);

                    break;
                case 'header':
                    $container->register($authServiceKey, Header::class)
                        ->addArgument($values['header_name'])
                        ->addArgument($values['header_value']);

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

    private function configureClient(ContainerBuilder $container, string $clientName, array $arguments): void
    {
        $serviceId = 'httplug.client.'.$clientName;

        $container->registerAliasForArgument($serviceId, ClientInterface::class, $clientName);
        $container->registerAliasForArgument($serviceId, HttpAsyncClient::class, $clientName);

        $plugins = [];
        foreach ($arguments['plugins'] as $plugin) {
            $pluginName = key($plugin);
            $pluginConfig = current($plugin);

            switch ($pluginName) {
                case 'reference':
                    $plugins[] = $pluginConfig['id'];
                    break;
                case 'authentication':
                    $plugins = array_merge($plugins, $this->configureAuthentication($container, $pluginConfig, $serviceId.'.authentication'));
                    break;
                case 'vcr':
                    $this->useVcrPlugin = true;
                    $plugins = array_merge($plugins, $this->configureVcrPlugin($container, $pluginConfig, $serviceId.'.vcr'));
                    break;
                default:
                    $plugins[] = $this->configurePlugin($container, $serviceId, $pluginName, $pluginConfig);
            }
        }

        if (empty($arguments['service'])) {
            $container
                ->register($serviceId.'.client', ClientInterface::class)
                ->setFactory([new Reference($arguments['factory']), 'createClient'])
                ->addArgument($arguments['config'])
                ->setPublic(false);
        } else {
            $container
                ->setAlias($serviceId.'.client', new Alias($arguments['service'], false));
        }

        $definition = $container
            ->register($serviceId, PluginClient::class)
            ->setFactory([new Reference(PluginClientFactory::class), 'createClient'])
            ->addArgument(new Reference($serviceId.'.client'))
            ->addArgument(
                array_map(
                    fn($id) => new Reference($id),
                    $plugins
                )
            )
            ->addArgument([
                'client_name' => $clientName,
            ])
            ->addTag(self::HTTPLUG_CLIENT_TAG)
        ;

        if (is_bool($arguments['public'])) {
            $definition->setPublic($arguments['public']);
        }

        /*
         * Decorate the client with clients from client-common
         */
        if ($arguments['flexible_client']) {
            $container
                ->register($serviceId.'.flexible', FlexibleHttpClient::class)
                ->addArgument(new Reference($serviceId.'.flexible.inner'))
                ->setPublic($arguments['public'] ? true : false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['http_methods_client']) {
            $container
                ->register($serviceId.'.http_methods', HttpMethodsClient::class)
                ->setArguments([new Reference($serviceId.'.http_methods.inner'), new Reference('httplug.psr17_request_factory'), new Reference('httplug.psr17_stream_factory')])
                ->setPublic($arguments['public'] ? true : false)
                ->setDecoratedService($serviceId)
            ;
        }

        if ($arguments['batch_client']) {
            $container
                ->register($serviceId.'.batch_client', BatchClient::class)
                ->setArguments([new Reference($serviceId.'.batch_client.inner')])
                ->setPublic($arguments['public'] ? true : false)
                ->setDecoratedService($serviceId)
            ;
        }
    }

    /**
     * Create a URI object with the default URI factory.
     *
     * @param string $serviceId Name of the private service to create
     * @param string $uri       String representation of the URI
     */
    private function createUri(ContainerBuilder $container, string $serviceId, string $uri): void
    {
        $container
            ->register($serviceId, UriInterface::class)
            ->setPublic(false)
            ->setFactory([new Reference('httplug.psr17_uri_factory'), 'createUri'])
            ->addArgument($uri)
        ;
    }

    /**
     * Make the user can select what client is used for auto discovery. If none is provided, a service will be created
     * by finding a client using auto discovery.
     */
    private function configureAutoDiscoveryClients(ContainerBuilder $container, array $config): void
    {
        $httpClient = $config['discovery']['client'];
        if ('auto' !== $httpClient) {
            $container->removeDefinition('httplug.auto_discovery.auto_discovered_client');
            $container->removeDefinition('httplug.collector.auto_discovered_client');

            if (!empty($httpClient)) {
                $container->setAlias('httplug.auto_discovery.auto_discovered_client', $httpClient);
                $container->getAlias('httplug.auto_discovery.auto_discovered_client')->setPublic(false);
            }
        }

        $asyncHttpClient = $config['discovery']['async_client'];
        if ('auto' !== $asyncHttpClient) {
            $container->removeDefinition('httplug.auto_discovery.auto_discovered_async');
            $container->removeDefinition('httplug.collector.auto_discovered_async');

            if (!empty($asyncHttpClient)) {
                $container->setAlias('httplug.auto_discovery.auto_discovered_async', $asyncHttpClient);
                $container->getAlias('httplug.auto_discovery.auto_discovered_async')->setPublic(false);
            }
        }

        if (null === $httpClient && null === $asyncHttpClient) {
            $container->removeDefinition('httplug.strategy');

            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * Configure a plugin using the parent definition from plugins.xml.
     *
     * @return string configured service id
     */
    private function configurePlugin(ContainerBuilder $container, string $serviceId, string $pluginName, array $pluginConfig): string
    {
        $pluginServiceId = $serviceId.'.plugin.'.$pluginName;

        $definition = new ChildDefinition('httplug.plugin.'.$pluginName);

        $this->configurePluginByName($pluginName, $definition, $pluginConfig, $container, $pluginServiceId);
        $container->setDefinition($pluginServiceId, $definition);

        return $pluginServiceId;
    }

    private function configureVcrPlugin(ContainerBuilder $container, array $config, string $prefix): array
    {
        $recorder = $config['recorder'];
        $recorderId = in_array($recorder, ['filesystem', 'in_memory']) ? 'httplug.plugin.vcr.recorder.'.$recorder : $recorder;
        $namingStrategyId = $config['naming_strategy'];
        $replayId = $prefix.'.replay';
        $recordId = $prefix.'.record';

        if ('filesystem' === $recorder) {
            $recorderDefinition = new ChildDefinition('httplug.plugin.vcr.recorder.filesystem');
            $recorderDefinition->replaceArgument(0, $config['fixtures_directory']);
            $recorderId = $prefix.'.recorder';

            $container->setDefinition($recorderId, $recorderDefinition);
        }

        if ('default' === $config['naming_strategy']) {
            $namingStrategyId = $prefix.'.naming_strategy';
            $namingStrategy = new ChildDefinition('httplug.plugin.vcr.naming_strategy.path');

            if (!empty($config['naming_strategy_options'])) {
                $namingStrategy->setArguments([$config['naming_strategy_options']]);
            }

            $container->setDefinition($namingStrategyId, $namingStrategy);
        }

        $arguments = [
            new Reference($namingStrategyId),
            new Reference($recorderId),
        ];
        $record = new Definition(RecordPlugin::class, $arguments);
        $replay = new Definition(ReplayPlugin::class, $arguments);
        $plugins = [];

        switch ($config['mode']) {
            case 'replay':
                $container->setDefinition($replayId, $replay);
                $plugins[] = $replayId;
                break;
            case 'replay_or_record':
                $replay->setArgument(2, false);
                $container->setDefinition($replayId, $replay);
                $container->setDefinition($recordId, $record);
                $plugins[] = $replayId;
                $plugins[] = $recordId;
                break;
            case 'record':
                $container->setDefinition($recordId, $record);
                $plugins[] = $recordId;
                break;
        }

        return $plugins;
    }
}
