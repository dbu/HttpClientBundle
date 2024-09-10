<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException;
use Http\Message\Formatter as MessageFormatter;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This class is a decorator for any Http\Message\Formatter with the the ability to format exceptions and requests as
 * cURL commands.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Formatter implements MessageFormatter
{
    public function __construct(
        private readonly MessageFormatter $formatter,
        private readonly MessageFormatter $curlFormatter,
    ) {
    }

    public function formatException(\Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $this->formatter->formatResponseForRequest($exception->getResponse(), $exception->getRequest());
        }

        if ($exception instanceof TransferException || $exception instanceof NetworkExceptionInterface) {
            return sprintf('Transfer error: %s', $exception->getMessage());
        }

        return sprintf('Unexpected exception of type "%s": %s', $exception::class, $exception->getMessage());
    }

    public function formatRequest(RequestInterface $request): string
    {
        return $this->formatter->formatRequest($request);
    }

    public function formatResponseForRequest(ResponseInterface $response, RequestInterface $request): string
    {
        if (method_exists($this->formatter, 'formatResponseForRequest')) {
            return $this->formatter->formatResponseForRequest($response, $request);
        }

        return $this->formatter->formatResponse($response);
    }

    public function formatResponse(ResponseInterface $response): string
    {
        return $this->formatter->formatResponse($response);
    }

    /**
     * Format the RequestInterface as a cURL command that can be copied to the command line.
     */
    public function formatAsCurlCommand(RequestInterface $request): string
    {
        return $this->curlFormatter->formatRequest($request);
    }
}
