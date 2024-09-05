<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use Http\Client\Common\HttpMethodsClient;
use Http\Client\Common\PluginClient;
use Http\Client\Common\PluginClientFactory;
use Http\Discovery\Psr18ClientDiscovery;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Issue206 extends WebTestCase
{
    public function testCustomClientDoesNotCauseException(): void
    {
        static::bootKernel();
        $container = static::$kernel->getContainer();
        PluginClientFactory::setFactory([$container->get(PluginClientFactory::class), 'createClient']);

        // Create a client
        $myCustomClient = new HttpMethodsClient(Psr18ClientDiscovery::find(), new Psr17Factory(), new Psr17Factory());
        $pluginClient = (new PluginClientFactory())->createClient($myCustomClient, []);

        // If we get to this line, no exceptions has been thrown.
        $this->assertInstanceOf(PluginClient::class, $pluginClient);
    }
}
