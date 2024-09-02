<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Http\Client\Common\Plugin;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * The StackPlugin must be used as first Plugin in a client stack. It's used to detect when a new request start by
 * creating a new Stack and pushing it to the Collector.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
class StackPlugin implements Plugin
{
    use Plugin\VersionBridgePlugin;

    public function __construct(
        private readonly Collector $collector,
        private readonly Formatter $formatter,
        private readonly string $client,
    ) {
    }

    protected function doHandleRequest(RequestInterface $request, callable $next, callable $first)
    {
        $stack = new Stack($this->client, $this->formatter->formatRequest($request));

        $this->collector->addStack($stack);
        $this->collector->activateStack($stack);

        $onFulfilled = function (ResponseInterface $response) use ($stack, $request) {
            $stack->setResponse($this->formatter->formatResponseForRequest($response, $request));

            return $response;
        };

        $onRejected = function (\Exception $exception) use ($stack): void {
            $stack->setResponse($this->formatter->formatException($exception));
            $stack->setFailed(true);

            throw $exception;
        };

        try {
            return $next($request)->then($onFulfilled, $onRejected);
        } finally {
            $this->collector->deactivateStack($stack);
        }
    }
}
