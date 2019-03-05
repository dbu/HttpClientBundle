<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Request as GuzzleRequest;
use Http\Adapter\Guzzle7\Client;
use Http\Client\Common\Plugin\RedirectPlugin;
use Http\Client\Common\PluginClient;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\StackPlugin;
use Nyholm\NSA;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profiler;

final class ServiceInstantiationTest extends WebTestCase
{
    public function testHttpClient(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle7 adapter is not installed');
        }

        static::bootKernel();
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    public function testHttpClientNoDebug(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle7 adapter is not installed');
        }

        static::bootKernel(['debug' => false]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('httplug.client'));
        $client = $container->get('httplug.client');
        $this->assertInstanceOf(ClientInterface::class, $client);
    }

    /**
     * @group legacy
     */
    public function testDebugToolbar(): void
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();
        $this->assertTrue($container->has('profiler'));
        $profiler = $container->get('profiler');
        $this->assertInstanceOf(Profiler::class, $profiler);
        $this->assertTrue($profiler->has('httplug'));
        $collector = $profiler->get('httplug');
        $this->assertInstanceOf(Collector::class, $collector);
    }

    public function testProfilingShouldNotChangeServiceReference(): void
    {
        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();

        $this->assertInstanceof(RedirectPlugin::class, $container->get('app.http.plugin.custom'));
    }

    public function testProfilingDecoration(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle7 adapter is not installed');
        }

        static::bootKernel(['debug' => true]);
        $container = static::$kernel->getContainer();

        $client = $container->get('httplug.client.acme');

        $this->assertInstanceOf(PluginClient::class, $client);
        $this->assertInstanceOf(ProfileClient::class, NSA::getProperty($client, 'client'));

        $plugins = NSA::getProperty($client, 'plugins');

        $this->assertInstanceOf(StackPlugin::class, $plugins[0]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[1]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[2]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[3]);
        $this->assertInstanceOf(ProfilePlugin::class, $plugins[4]);
    }

    public function testProfilingPsr18Decoration(): void
    {
        if (!interface_exists(ClientInterface::class)) {
            $this->markTestSkipped('PSR-18 is not installed');
        }

        static::bootKernel(['debug' => true, 'environment' => 'psr18']);
        $container = static::$kernel->getContainer();

        $client = $container->get('httplug.client.my_psr18');
        $this->assertInstanceOf(PluginClient::class, $client);
        $profileClient = NSA::getProperty($client, 'client');
        $this->assertInstanceOf(ProfileClient::class, $profileClient);

        $flexibleClient = NSA::getProperty($profileClient, 'client');
        $psr18Client = NSA::getProperty($flexibleClient, 'httpClient');
        $this->assertInstanceOf(ClientInterface::class, $psr18Client);

        $response = $client->sendRequest(new GuzzleRequest('GET', 'https://example.com'));
        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    protected static function bootKernel(array $options = []): KernelInterface
    {
        parent::bootKernel($options);

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::$kernel->getContainer()->get('event_dispatcher');

        $event = new RequestEvent(static::$kernel, SymfonyRequest::create('/'), HttpKernelInterface::MAIN_REQUEST);

        $dispatcher->dispatch($event, KernelEvents::REQUEST);

        return static::$kernel;
    }
}
