<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\ClientFactory;

use Buzz\Client\FileGetContents;
use Http\HttplugBundle\ClientFactory\BuzzFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
final class BuzzFactoryTest extends TestCase
{
    public function testCreateClient(): void
    {
        if (!class_exists(FileGetContents::class)) {
            $this->markTestSkipped('Buzz client is not installed');
        }

        $factory = new BuzzFactory($this->getMockBuilder(ResponseFactoryInterface::class)->getMock());
        $client = $factory->createClient();

        $this->assertInstanceOf(FileGetContents::class, $client);
    }
}
