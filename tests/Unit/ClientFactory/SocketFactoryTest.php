<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Client\Socket\Client;
use Http\HttplugBundle\ClientFactory\SocketFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class SocketFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Socket client is not installed');
        }

        $factory = new SocketFactory();
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
