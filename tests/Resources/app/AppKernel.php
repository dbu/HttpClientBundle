<?php

declare(strict_types=1);

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader as RoutingPhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class AppKernel extends Kernel
{
    private static ?string $cacheDir = null;

    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new \Symfony\Bundle\TwigBundle\TwigBundle(),
            new \Http\HttplugBundle\HttplugBundle(),
            new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) use ($loader): void {
            $container->loadFromExtension('framework', [
                'router' => [
                    'resource' => 'kernel::loadRoutes',
                    'type' => 'service',
                    'utf8' => true,
                ],
            ]);

            $container->register('kernel', static::class)
                ->addTag('routing.route_loader')
                ->setAutoconfigured(true)
                ->setSynthetic(true)
                ->setPublic(true)
            ;
            // hack around problem with lowest versions build
            if ('dev' === ($env = $this->getEnvironment())) {
                $env = 'test';
            }

            $loader->load(__DIR__."/config/config_$env.yml");
            if ($this->isDebug()) {
                $loader->load(__DIR__.'/config/config_debug.yml');
            }
        });
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));

        $collection = new RouteCollection();
        $collection->add('/', new Route('/', ['_controller' => 'kernel::indexAction']));

        $routes = new RoutingConfigurator($collection, $kernelLoader, $file, $file);
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('_profiler');

        return $collection;
    }

    public function getCacheDir(): string
    {
        if (null === self::$cacheDir) {
            self::$cacheDir = uniqid('cache');
        }

        return sys_get_temp_dir().'/httplug-bundle/'.self::$cacheDir;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/httplug-bundle/logs';
    }

    public function indexAction(): Response
    {
        return new Response();
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PublicServicesForFunctionalTestsPass());
    }
}

class PublicServicesForFunctionalTestsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $services = [
            'httplug.strategy',
            'httplug.auto_discovery.auto_discovered_client',
            'httplug.auto_discovery.auto_discovered_async',
            'httplug.async_client.default',
            'httplug.client.default',
            'app.http.plugin.custom',
            'httplug.client.acme',
        ];
        foreach ($services as $service) {
            if ($container->hasDefinition($service)) {
                $container->getDefinition($service)->setPublic(true);
            }
        }

        $aliases = [
            'httplug.client',
        ];
        foreach ($aliases as $alias) {
            if ($container->hasAlias($alias)) {
                $container->getAlias($alias)->setPublic(true);
            }
        }
    }
}
