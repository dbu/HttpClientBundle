<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Collector;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Http\Client\Common\Plugin;
use Http\Client\Exception\TransferException;
use Http\HttplugBundle\Collector\Collector;
use Http\HttplugBundle\Collector\Formatter;
use Http\HttplugBundle\Collector\ProfilePlugin;
use Http\HttplugBundle\Collector\Stack;
use Http\Message\Formatter as MessageFormatter;
use Http\Message\Formatter\SimpleFormatter;
use Http\Promise\FulfilledPromise;
use Http\Promise\Promise;
use Http\Promise\RejectedPromise;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class ProfilePluginTest extends TestCase
{
    private Plugin&MockObject $plugin;

    private RequestInterface $request;

    private ResponseInterface $response;

    private Promise $fulfilledPromise;

    private TransferException $exception;

    private Promise $rejectedPromise;

    private ProfilePlugin $subject;

    private Collector $collector;

    public function setUp(): void
    {
        $this->collector = new Collector();
        $messageFormatter = $this->createMock(SimpleFormatter::class);
        $formatter = new Formatter($messageFormatter, $this->createMock(MessageFormatter::class));

        $this->plugin = $this->createMock(Plugin::class);
        $this->request = new Request('GET', '/');
        $this->response = new Response();
        $this->fulfilledPromise = new FulfilledPromise($this->response);
        $currentStack = new Stack('default', 'FormattedRequest');
        $this->collector->activateStack($currentStack);
        $this->exception = new TransferException();
        $this->rejectedPromise = new RejectedPromise($this->exception);

        $this->plugin
            ->method('handleRequest')
            ->willReturnCallback(fn ($request, $next, $first) => $next($request))
        ;

        $messageFormatter
            ->method('formatRequest')
            ->with($this->identicalTo($this->request))
            ->willReturn('FormattedRequest')
        ;

        $messageFormatter
            ->method('formatResponseForRequest')
            ->with($this->identicalTo($this->response), $this->identicalTo($this->request))
            ->willReturn('FormattedResponse')
        ;

        $this->subject = new ProfilePlugin(
            $this->plugin,
            $this->collector,
            $formatter,
        );
    }

    public function testCallDecoratedPlugin(): void
    {
        $this->plugin
            ->expects($this->once())
            ->method('handleRequest')
            ->with($this->request)
        ;

        $this->subject->handleRequest($this->request, fn () => $this->fulfilledPromise, function (): void {
        });
    }

    public function testProfileIsInitialized(): void
    {
        $this->subject->handleRequest($this->request, fn () => $this->fulfilledPromise, function (): void {
        });

        $activeStack = $this->collector->getActiveStack();
        $this->assertCount(1, $activeStack->getProfiles());
        $profile = $activeStack->getProfiles()[0];
        $this->assertEquals($this->plugin::class, $profile->getPlugin());
    }

    public function testCollectRequestInformations(): void
    {
        $this->subject->handleRequest($this->request, fn () => $this->fulfilledPromise, function (): void {
        });

        $activeStack = $this->collector->getActiveStack();
        $this->assertCount(1, $activeStack->getProfiles());
        $profile = $activeStack->getProfiles()[0];
        $this->assertEquals('FormattedRequest', $profile->getRequest());
    }

    public function testOnFulfilled(): void
    {
        $promise = $this->subject->handleRequest($this->request, fn () => $this->fulfilledPromise, function (): void {
        });

        $this->assertEquals($this->response, $promise->wait());

        $activeStack = $this->collector->getActiveStack();
        $this->assertCount(1, $activeStack->getProfiles());
        $profile = $activeStack->getProfiles()[0];
        $this->assertEquals('FormattedResponse', $profile->getResponse());
    }

    public function testOnRejected(): void
    {
        $promise = $this->subject->handleRequest($this->request, fn () => $this->rejectedPromise, function (): void {
        });

        $activeStack = $this->collector->getActiveStack();
        $this->assertCount(1, $activeStack->getProfiles());

        $this->expectException(TransferException::class);
        $promise->wait();
    }
}
