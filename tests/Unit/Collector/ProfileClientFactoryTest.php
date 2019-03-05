<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\HttplugBundle\ClientFactory\ClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfileClientFactory;
use Http\Message\Formatter as MessageFormatter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\Stopwatch\Stopwatch;

final class ProfileClientFactoryTest extends TestCase
{
    private Collector $collector;

    private Formatter $formatter;

    private Stopwatch&MockObject $stopwatch;

    private ClientInterface&MockObject $client;

    public function setUp(): void
    {
        $this->collector = new Collector();
        $this->formatter = new Formatter($this->createMock(MessageFormatter::class), $this->createMock(MessageFormatter::class));
        $this->stopwatch = $this->createMock(Stopwatch::class);
        $this->client = $this->createMock(ClientInterface::class);
    }

    public function testCreateClientFromClientFactory(): void
    {
        $factory = $this->getMockBuilder(ClientFactory::class)->getMock();
        $factory->method('createClient')->willReturn($this->client);

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }

    public function testCreateClientFromCallable(): void
    {
        $factory = fn ($config) => $this->client;

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter, $this->stopwatch);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }
}
