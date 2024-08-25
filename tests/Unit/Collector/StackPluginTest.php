<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\HttpException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\StackPlugin;
use Http\Message\Formatter as MessageFormatter;
use Http\Message\Formatter\SimpleFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class StackPluginTest extends TestCase
{
    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var StackPlugin
     */
    private $subject;

    public function setUp(): void
    {
        $this->collector = new Collector();
        $messageFormatter = $this->createMock(SimpleFormatter::class);
        $formatter = new Formatter($messageFormatter, $this->createMock(MessageFormatter::class));
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->exception = new HttpException('', $this->request, $this->response);

        $messageFormatter
            ->method('formatRequest')
            ->with($this->request)
            ->willReturn('FormattedRequest')
        ;

        $messageFormatter
            ->method('formatResponseForRequest')
            ->with($this->response, $this->request)
            ->willReturn('FormattedResponse')
        ;

        $this->subject = new StackPlugin($this->collector, $formatter, 'default');
    }

    public function testStackIsInitialized(): void
    {
        $next = fn () => new FulfilledPromise($this->response);

        $this->subject->handleRequest($this->request, $next, function (): void {
        });

        $this->assertNull($this->collector->getActiveStack());
        $stacks = $this->collector->getStacks();
        $this->assertCount(1, $stacks);
        $this->assertEquals('default', $stacks[0]->getClient());
        $this->assertEquals('FormattedRequest', $stacks[0]->getRequest());
    }

    public function testOnFulfilled(): void
    {
        $next = fn () => new FulfilledPromise($this->response);

        $promise = $this->subject->handleRequest($this->request, $next, function (): void {
        });

        $this->assertEquals($this->response, $promise->wait());
        $this->assertNull($this->collector->getActiveStack());
        $stacks = $this->collector->getStacks();
        $this->assertCount(1, $stacks);
        $this->assertEquals('FormattedResponse', $stacks[0]->getResponse());
    }

    public function testOnRejected(): void
    {
        $next = fn () => new RejectedPromise($this->exception);

        $promise = $this->subject->handleRequest($this->request, $next, function (): void {
        });

        $this->assertNull($this->collector->getActiveStack());
        $stacks = $this->collector->getStacks();
        $this->assertCount(1, $stacks);
        $this->assertEquals('FormattedResponse', $stacks[0]->getResponse());
        $this->assertTrue($stacks[0]->isFailed());

        $this->expectException(\Exception::class);
        $promise->wait();
    }

    public function testOnException(): void
    {
        $next = function (): void {
            throw new \Exception();
        };

        $this->expectException(\Exception::class);
        $this->subject->handleRequest($this->request, $next, function (): void {
        });
    }

    public function testOnError(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            $this->expectException(\DivisionByZeroError::class);
        } else {
            $this->expectException(Warning::class);
        }

        $next = fn () => 2 / 0;

        $this->subject->handleRequest($this->request, $next, function (): void {
        });
    }
}
