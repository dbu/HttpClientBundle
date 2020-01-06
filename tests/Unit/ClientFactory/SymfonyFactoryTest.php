<?php

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\HttplugBundle\ClientFactory\SymfonyFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Symfony\Component\HttpClient\HttplugClient;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SymfonyFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(HttplugClient::class)) {
            $this->markTestSkipped('Symfony Http client is not installed');
        }

        $factory = new SymfonyFactory(
            $this->getMockBuilder(ResponseFactoryInterface::class)->getMock(),
            $this->getMockBuilder(StreamFactoryInterface::class)->getMock()
        );
        $client = $factory->createClient();

        $this->assertInstanceOf(HttplugClient::class, $client);
    }
}
