<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use Http\Client\HttpAsyncClient;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfileClient;
use Http\HttplugBundle\Collector\Stack;
use Http\Message\Formatter as MessageFormatter;
use Http\Message\Formatter\SimpleFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class ProfileClientTest extends TestCase
{
    private Collector $collector;

    private CombinedClientInterface&MockObject $client;

    private RequestInterface $request;

    private StopwatchEvent $stopwatchEvent;

    private ProfileClient $subject;

    private ResponseInterface $response;

    private Promise $fulfilledPromise;

    private RejectedPromise $rejectedPromise;

    public function setUp(): void
    {
        $messageFormatter = $this->createMock(SimpleFormatter::class);
        $formatter = new Formatter($messageFormatter, $this->createMock(MessageFormatter::class));
        $this->collector = new Collector();
        $stopwatch = $this->createMock(Stopwatch::class);

        $activeStack = new Stack('default', 'FormattedRequest');
        $this->collector->activateStack($activeStack);
        $this->client = $this->getMockBuilder(CombinedClientInterface::class)->getMock();
        $uri = new Uri('https://example.com/target');
        $this->request = new Request('GET', $uri);
        $this->stopwatchEvent = $this->createMock(StopwatchEvent::class);
        $this->subject = new ProfileClient($this->client, $this->collector, $formatter, $stopwatch);
        $this->response = new Response();
        $exception = new \Exception('test');
        $this->fulfilledPromise = new FulfilledPromise($this->response);
        $this->rejectedPromise = new RejectedPromise($exception);

        $messageFormatter
            ->method('formatResponseForRequest')
            ->with($this->response, $this->request)
            ->willReturn('FormattedResponse')
        ;

        $stopwatch
            ->method('start')
            ->willReturn($this->stopwatchEvent)
        ;

        $this->stopwatchEvent
            ->method('getDuration')
            ->willReturn(42)
        ;
    }

    public function testSendRequest(): void
    {
        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->response)
        ;

        $response = $this->subject->sendRequest($this->request);

        $this->assertEquals($this->response, $response);
        $activeStack = $this->collector->getActiveStack();
        $this->assertInstanceOf(Stack::class, $activeStack);
        $this->assertEquals('GET', $activeStack->getRequestMethod());
        $this->assertEquals('/target', $activeStack->getRequestTarget());
        $this->assertEquals('example.com', $activeStack->getRequestHost());
        $this->assertEquals('https', $activeStack->getRequestScheme());
    }

    public function testSendRequestTypeError()
    {
        $this->client
            ->expects($this->once())
            ->method('sendRequest')
            ->willReturnCallback(function (): void {
                throw new \Error('You set string to int prop');
            });

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('You set string to int prop');
        $this->subject->sendRequest($this->request);
    }

    public function testSendAsyncRequest(): void
    {
        $this->client
            ->expects($this->once())
            ->method('sendAsyncRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn($this->fulfilledPromise)
        ;

        $promise = $this->subject->sendAsyncRequest($this->request);

        $this->assertEquals($this->fulfilledPromise, $promise);
        $activeStack = $this->collector->getActiveStack();
        $this->assertInstanceOf(Stack::class, $activeStack);
        $this->assertEquals('GET', $activeStack->getRequestMethod());
        $this->assertEquals('/target', $activeStack->getRequestTarget());
        $this->assertEquals('example.com', $activeStack->getRequestHost());
        $this->assertEquals('https', $activeStack->getRequestScheme());
    }

    public function testOnFulfilled(): void
    {
        $this->stopwatchEvent
            ->expects($this->once())
            ->method('stop')
        ;

        $this->client
            ->method('sendAsyncRequest')
            ->willReturn($this->fulfilledPromise)
        ;

        $this->subject->sendAsyncRequest($this->request);

        $activeStack = $this->collector->getActiveStack();
        $this->assertInstanceOf(Stack::class, $activeStack);
        $this->assertEquals(42, $activeStack->getDuration());
        $this->assertEquals(200, $activeStack->getResponseCode());
        $this->assertEquals('FormattedResponse', $activeStack->getClientResponse());
    }

    public function testOnRejected(): void
    {
        $this->stopwatchEvent
            ->expects($this->once())
            ->method('stop')
        ;

        $this->client
            ->method('sendAsyncRequest')
            ->willReturn($this->rejectedPromise)
        ;

        $this->subject->sendAsyncRequest($this->request);

        $activeStack = $this->collector->getActiveStack();
        $this->assertInstanceOf(Stack::class, $activeStack);
        $this->assertEquals(42, $activeStack->getDuration());
        $this->assertEquals('Unexpected exception of type "Exception": test', $activeStack->getClientException());
    }
}

interface CombinedClientInterface extends ClientInterface, HttpAsyncClient
{
}
