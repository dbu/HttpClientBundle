<?php

namespace Http\HttplugBundle\Tests\Unit\Collector;

use Http\Client\HttpClient;
use Http\HttplugBundle\ClientFactory\ClientFactory;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfileClientFactory;

class ProfileClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var HttpClient
     */
    private $client;

    public function setUp()
    {
        $this->collector = $this->getMockBuilder(Collector::class)->disableOriginalConstructor()->getMock();
        $this->formatter = $this->getMockBuilder(Formatter::class)->disableOriginalConstructor()->getMock();
        $this->client = $this->getMockBuilder(HttpClient::class)->getMock();
    }

    public function testCreateClientFromClientFactory()
    {
        $factory = $this->getMockBuilder(ClientFactory::class)->getMock();
        $factory->method('createClient')->willReturn($this->client);

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }

    public function testCreateClientFromCallable()
    {
        $factory = function ($config) {
            return $this->client;
        };

        $subject = new ProfileClientFactory($factory, $this->collector, $this->formatter);

        $this->assertInstanceOf(ProfileClient::class, $subject->createClient());
    }
}
