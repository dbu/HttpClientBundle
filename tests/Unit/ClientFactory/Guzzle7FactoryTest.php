<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\Guzzle7\Client;
use Http\HttplugBundle\ClientFactory\Guzzle7Factory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class Guzzle7FactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle7 adapter is not installed');
        }

        $factory = new Guzzle7Factory();
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
