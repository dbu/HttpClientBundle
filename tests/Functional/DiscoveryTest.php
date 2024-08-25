<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection\Compiler;

use Http\Adapter\Guzzle7\Client;
use Http\Client\HttpAsyncClient;
use Http\Discovery\Psr18ClientDiscovery;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\ContainerBuilderHasAliasConstraint;
use PHPUnit\Framework\Constraint\LogicalNot;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
final class DiscoveryTest extends AbstractExtensionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.debug', true);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new HttplugExtension(),
        ];
    }

    public function testDiscoveryFallbacks(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService('httplug.client.default', ClientInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_request_factory.default', RequestFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_response_factory.default', ResponseFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_uri_factory.default', UriFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_stream_factory.default', StreamFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.async_client.default', HttpAsyncClient::class);
    }

    public function testDiscoveryPartialFallbacks(): void
    {
        $this->load();
        $this->setDefinition('httplug.client.default', new Definition(Client::class));

        $this->assertContainerBuilderHasService('httplug.client.default', Client::class);
        $this->assertContainerBuilderHasService('httplug.psr17_request_factory.default', RequestFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_response_factory.default', ResponseFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_uri_factory.default', UriFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.psr17_stream_factory.default', StreamFactoryInterface::class);
        $this->assertContainerBuilderHasService('httplug.async_client.default', HttpAsyncClient::class);
    }

    public function testNoDiscoveryFallbacks(): void
    {
        $this->setDefinition('httplug.client.default', new Definition(ClientInterface::class));
        $this->setDefinition('httplug.psr17_request_factory.default', new Definition(RequestFactoryInterface::class));
        $this->setDefinition('httplug.psr17_uri_factory.default', new Definition(UriFactoryInterface::class));
        $this->setDefinition('httplug.psr17_stream_factory.default', new Definition(StreamFactoryInterface::class));
        $this->setDefinition('httplug.async_client.default', new Definition(HttpAsyncClient::class));

        $this->load();

        $this->assertContainerBuilderHasService('httplug.client.default', ClientInterface::class);
        $clientDefinition = $this->container->getDefinition('httplug.client.default');
        $this->assertEquals([Psr18ClientDiscovery::class, 'find'], $clientDefinition->getFactory());
    }

    public function testEnableAutowiring(): void
    {
        $this->load([
            'default_client_autowiring' => true,
        ]);

        $this->assertContainerBuilderHasService('httplug.client.default');
        $this->assertContainerBuilderHasService('httplug.async_client.default');
        $this->assertContainerBuilderHasAlias(ClientInterface::class);
        $this->assertContainerBuilderHasAlias(HttpAsyncClient::class);
    }

    public function testDisableAutowiring(): void
    {
        if (PHP_VERSION_ID <= 70000) {
            $this->markTestSkipped();
        }

        $this->load([
            'default_client_autowiring' => false,
        ]);

        $this->assertContainerBuilderHasService('httplug.client.default');
        $this->assertContainerBuilderHasService('httplug.async_client.default');

        self::assertThat(
            $this->container,
            new LogicalNot(new ContainerBuilderHasAliasConstraint(ClientInterface::class))
        );
        self::assertThat(
            $this->container,
            new LogicalNot(new ContainerBuilderHasAliasConstraint(HttpAsyncClient::class))
        );
    }
}
