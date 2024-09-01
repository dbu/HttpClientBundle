<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\FlexibleHttpClient;
use Http\Client\Common\VersionBridgeClient;
use Http\Client\Exception\HttpException;
use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * The ProfileClient decorates any client that implement both HttpClient and HttpAsyncClient interfaces to gather target
 * url and response status code.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 * @final
 */
class ProfileClient implements HttpClient, HttpAsyncClient
{
    use VersionBridgeClient;

    /**
     * @var HttpClient|HttpAsyncClient
     */
    private $client;

    /**
     * @var Collector
     */
    private $collector;

    /**
     * @var Formatter
     */
    private $formatter;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var array
     */
    private $eventNames = [];

    private const STOPWATCH_CATEGORY = 'httplug';

    /**
     * @param HttpClient|HttpAsyncClient $client The client to profile. Client must implement HttpClient or
     *                                           HttpAsyncClient interface.
     */
    public function __construct($client, Collector $collector, Formatter $formatter, Stopwatch $stopwatch)
    {
        if (!(($client instanceof ClientInterface || $client instanceof HttpClient) && $client instanceof HttpAsyncClient)) {
            $client = new FlexibleHttpClient($client);
        }

        $this->client = $client;
        $this->collector = $collector;
        $this->formatter = $formatter;
        $this->stopwatch = $stopwatch;
    }

    public function sendAsyncRequest(RequestInterface $request)
    {
        $activateStack = true;
        $stack = $this->collector->getActiveStack();
        if (null === $stack) {
            //When using a discovered client not wrapped in a PluginClient, we don't have a stack from StackPlugin. So
            //we create our own stack and activate it!
            $stack = new Stack('Default', $this->formatter->formatRequest($request));
            $this->collector->addStack($stack);
            $this->collector->activateStack($stack);
            $activateStack = false;
        }

        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request), self::STOPWATCH_CATEGORY);

        $onFulfilled = function (ResponseInterface $response) use ($request, $event, $stack) {
            $this->collectResponseInformations($request, $response, $event, $stack);
            $event->stop();

            return $response;
        };

        $onRejected = function (\Exception $exception) use ($event, $stack) {
            $this->collectExceptionInformations($exception, $event, $stack);
            $event->stop();

            throw $exception;
        };

        $this->collector->deactivateStack($stack);

        try {
            return $this->client->sendAsyncRequest($request)->then($onFulfilled, $onRejected);
        } catch (\Exception $e) {
            $event->stop();

            throw $e;
        } finally {
            if ($activateStack) {
                //We only activate the stack when created by the StackPlugin.
                $this->collector->activateStack($stack);
            }
        }
    }

    protected function doSendRequest(RequestInterface $request)
    {
        $stack = $this->collector->getActiveStack();
        if (null === $stack) {
            //When using a discovered client not wrapped in a PluginClient, we don't have a stack from StackPlugin. So
            //we create our own stack but don't activate it.
            $stack = new Stack('Default', $this->formatter->formatRequest($request));
            $this->collector->addStack($stack);
        }

        $this->collectRequestInformations($request, $stack);
        $event = $this->stopwatch->start($this->getStopwatchEventName($request), self::STOPWATCH_CATEGORY);

        try {
            $response = $this->client->sendRequest($request);
            $this->collectResponseInformations($request, $response, $event, $stack);

            return $response;
        } catch (\Throwable $e) {
            $this->collectExceptionInformations($e, $event, $stack);

            throw $e;
        } finally {
            $event->stop();
        }
    }

    private function collectRequestInformations(RequestInterface $request, Stack $stack): void
    {
        $uri = $request->getUri();
        $stack->setRequestTarget($request->getRequestTarget());
        $stack->setRequestMethod($request->getMethod());
        $stack->setRequestScheme($uri->getScheme());
        $stack->setRequestPort($uri->getPort());
        $stack->setRequestHost($uri->getHost());
        $stack->setClientRequest($this->formatter->formatRequest($request));
        $stack->setCurlCommand($this->formatter->formatAsCurlCommand($request));
    }

    private function collectResponseInformations(RequestInterface $request, ResponseInterface $response, StopwatchEvent $event, Stack $stack): void
    {
        $stack->setDuration($event->getDuration());
        $stack->setResponseCode($response->getStatusCode());
        $stack->setClientResponse($this->formatter->formatResponseForRequest($response, $request));
    }

    private function collectExceptionInformations(\Throwable $exception, StopwatchEvent $event, Stack $stack): void
    {
        if ($exception instanceof HttpException) {
            $this->collectResponseInformations($exception->getRequest(), $exception->getResponse(), $event, $stack);
        }

        $stack->setDuration($event->getDuration());
        $stack->setClientException($this->formatter->formatException($exception));
    }

    private function getStopwatchEventName(RequestInterface $request): string
    {
        $name = sprintf('%s %s', $request->getMethod(), $request->getUri());

        if (isset($this->eventNames[$name])) {
            $name .= sprintf(' [#%d]', ++$this->eventNames[$name]);
        } else {
            $this->eventNames[$name] = 1;
        }

        return $name;
    }
}
