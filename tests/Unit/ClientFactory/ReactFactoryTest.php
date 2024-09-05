<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Http\Adapter\React\Client;
use Http\HttplugBundle\ClientFactory\ReactFactory;
use PHPUnit\Framework\TestCase;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class ReactFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('React adapter is not installed');
        }

        $factory = new ReactFactory();
        $client = $factory->createClient();

        $this->assertInstanceOf(Client::class, $client);
    }
}
