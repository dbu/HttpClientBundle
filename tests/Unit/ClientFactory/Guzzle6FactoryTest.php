<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\Guzzle6\Client;
use Http\HttplugBundle\ClientFactory\Guzzle6Factory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Guzzle6FactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle6 adapter is not installed');
        }

        $factory = new Guzzle6Factory();
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
