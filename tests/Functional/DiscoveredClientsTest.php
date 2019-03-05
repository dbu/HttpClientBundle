<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use Http\Adapter\Guzzle7\Client;
use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpAsyncClientDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Discovery\Strategy\CommonClassesStrategy;
use Http\Discovery\Strategy\CommonPsr17ClassesStrategy;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategyListener;
use Nyholm\NSA;
use Psr\Http\Client\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DiscoveredClientsTest extends WebTestCase
{
    public function testDiscoveredClient(): void
    {
        $container = $this->getCustomContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        $this->assertInstanceOf(ClientInterface::class, $service);
    }

    public function testDiscoveredAsyncClient(): void
    {
        $container = $this->getCustomContainer(false);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(HttpAsyncClient::class, $service);
    }

    public function testDiscoveredClientWithProfilingEnabled(): void
    {
        $container = $this->getCustomContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_client');

        $this->assertInstanceOf(ProfileClient::class, $service);
        $this->assertInstanceOf(ClientInterface::class, NSA::getProperty($service, 'client'));
    }

    public function testDiscoveredAsyncClientWithProfilingEnabled(): void
    {
        $container = $this->getCustomContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));

        $service = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(ProfileClient::class, $service);
        $this->assertInstanceOf(HttpAsyncClient::class, NSA::getProperty($service, 'client'));
    }

    /**
     * Test with httplug.discovery.client: "auto".
     */
    public function testDiscovery(): void
    {
        $container = $this->getCustomContainer(true);

        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertTrue($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertTrue($container->has('httplug.strategy'));

        $container->get('httplug.strategy');

        $httpClient = $container->get('httplug.auto_discovery.auto_discovered_client');
        $httpAsyncClient = $container->get('httplug.auto_discovery.auto_discovered_async');

        $this->assertInstanceOf(ProfileClient::class, $httpClient);
        $this->assertSame(Psr18ClientDiscovery::find(), $httpClient);
        $this->assertInstanceOf(ProfileClient::class, $httpAsyncClient);
        $this->assertSame(HttpAsyncClientDiscovery::find(), $httpAsyncClient);
    }

    /**
     * Test with httplug.discovery.client: null.
     */
    public function testDisabledDiscovery(): void
    {
        $container = $this->getCustomContainer(true, 'discovery_disabled');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertFalse($container->has('httplug.strategy'));
    }

    /**
     * Test with httplug.discovery.client: "httplug.client.acme".
     */
    public function testForcedDiscovery(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle7 adapter is not installed');
        }

        $container = $this->getCustomContainer(true, 'discovery_forced');

        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_client'));
        $this->assertFalse($container->has('httplug.auto_discovery.auto_discovered_async'));
        $this->assertTrue($container->has('httplug.strategy'));

        $container->get('httplug.strategy');

        $this->assertEquals($container->get('httplug.client.acme'), Psr18ClientDiscovery::find());
        $this->assertEquals($container->get('httplug.client.acme'), HttpAsyncClientDiscovery::find());
    }

    private function getCustomContainer($debug, $environment = 'test')
    {
        static::bootKernel(['debug' => $debug, 'environment' => $environment]);

        return static::$kernel->getContainer();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Reset values
        $strategy = new ConfiguredClientsStrategyListener(null, null);
        Psr18ClientDiscovery::setStrategies([CommonClassesStrategy::class, CommonPsr17ClassesStrategy::class]);
        $strategy->onEvent();
    }
}
