<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Request;
use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\StreamFactoryDiscovery;
use Http\Discovery\UriFactoryDiscovery;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Message\Formatter\CurlCommandFormatter;
use Http\Message\Formatter\FullHttpMessageFormatter;
use Http\Mock\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Stopwatch\Stopwatch;

class ProfilingTest extends TestCase
{
    private Collector $collector;

    private Formatter $formatter;

    private Stopwatch $stopwatch;

    public function setUp(): void
    {
        $this->collector = new Collector();
        $this->formatter = new Formatter(new FullHttpMessageFormatter(), new CurlCommandFormatter());
        $this->stopwatch = new Stopwatch();
    }

    public function testProfilingWithCachePlugin(): void
    {
        $client = $this->createClient([
            new Plugin\CachePlugin(new ArrayAdapter(), StreamFactoryDiscovery::find(), [
                'respect_response_cache_directives' => [],
                'default_ttl' => 86400,
            ]),
        ]);

        $client->sendRequest(new Request('GET', 'https://example.com'));
        $client->sendRequest(new Request('GET', 'https://example.com'));

        $this->assertCount(2, $this->collector->getStacks());
        $stack = $this->collector->getStacks()[1];
        $this->assertEquals('GET', $stack->getRequestMethod());
        $this->assertEquals('https', $stack->getRequestScheme());
        $this->assertEquals('/', $stack->getRequestTarget());
        $this->assertEquals('example.com', $stack->getRequestHost());
    }

    public function testProfilingWhenPluginThrowException(): void
    {
        $client = $this->createClient([
            new ExceptionThrowerPlugin(),
        ]);

        try {
            $this->expectException(\Exception::class);
            $client->sendRequest(new Request('GET', 'https://example.com'));
        } finally {
            $this->assertCount(1, $this->collector->getStacks());
            $stack = $this->collector->getStacks()[0];
            $this->assertEquals('GET', $stack->getRequestMethod());
            $this->assertEquals('https', $stack->getRequestScheme());
            $this->assertEquals('/', $stack->getRequestTarget());
            $this->assertEquals('example.com', $stack->getRequestHost());
        }
    }

    public function testProfiling(): void
    {
        $client = $this->createClient([
            new Plugin\AddHostPlugin(UriFactoryDiscovery::find()->createUri('https://example.com')),
            new Plugin\RedirectPlugin(),
            new Plugin\RetryPlugin(),
        ]);

        $client->sendRequest(new Request('GET', '/'));

        $this->assertCount(1, $this->collector->getStacks());
        $stack = $this->collector->getStacks()[0];
        $this->assertCount(3, $stack->getProfiles());
        $this->assertEquals('GET', $stack->getRequestMethod());
        $this->assertEquals('https', $stack->getRequestScheme());
        $this->assertEquals('/', $stack->getRequestTarget());
        $this->assertEquals('example.com', $stack->getRequestHost());
    }

    private function createClient(array $plugins, $clientName = 'Acme', array $clientOptions = [])
    {
        $plugins = array_map(fn (Plugin $plugin) => new ProfilePlugin($plugin, $this->collector, $this->formatter, $plugin::class), $plugins);

        array_unshift($plugins, new StackPlugin($this->collector, $this->formatter, $clientName));

        $client = new Client();
        $client = new ProfileClient($client, $this->collector, $this->formatter, $this->stopwatch);
        $client = new PluginClient($client, $plugins, $clientOptions);

        return $client;
    }
}

class ExceptionThrowerPlugin implements Plugin
{
    use Plugin\VersionBridgePlugin;

    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first): void
    {
        throw new \Exception();
    }
}
