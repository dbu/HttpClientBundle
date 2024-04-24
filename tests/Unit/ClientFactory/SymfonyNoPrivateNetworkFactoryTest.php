<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\SymfonyFactory;
use Http\HttplugBundle\ClientFactory\SymfonyNoPrivateNetworkFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttplugClient;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyNoPrivateNetworkFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(HttplugClient::class)) {
            $this->markTestSkipped('Symfony Http client is not installed');
        }

        if (!class_exists(NoPrivateNetworkHttpClient::class)) {
            $this->markTestSkipped('No Private Network http client is available in this Symfony version.');
        }

        $factory = new SymfonyNoPrivateNetworkFactory(
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class)
        );
        $client = $factory->createClient();
        $reflection = new \ReflectionObject($client);
        $internalClient = $reflection->getProperty('client');
        $internalClient->setAccessible(true);

        $this->assertInstanceOf(NoPrivateNetworkHttpClient::class, $internalClient->getValue($client));
    }
}
