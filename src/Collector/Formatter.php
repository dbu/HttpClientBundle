<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

use Exception;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\TransferException;
use Http\Message\Formatter as MessageFormatter;
use Http\Message\Formatter\CurlCommandFormatter;
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
 * @final
 */
class Formatter implements MessageFormatter
{
    /**
     * @var MessageFormatter
     */
    private $formatter;

    /**
     * @var CurlCommandFormatter
     */
    private $curlFormatter;

    public function __construct(MessageFormatter $formatter, MessageFormatter $curlFormatter)
    {
        $this->formatter = $formatter;
        $this->curlFormatter = $curlFormatter;
    }

    /**
     * Formats an exception.
     *
     * @return string
     */
    public function formatException(\Throwable $exception)
    {
        if ($exception instanceof HttpException) {
            return $this->formatter->formatResponseForRequest($exception->getResponse(), $exception->getRequest());
        }

        if ($exception instanceof TransferException || $exception instanceof NetworkExceptionInterface) {
            return sprintf('Transfer error: %s', $exception->getMessage());
        }

        return sprintf('Unexpected exception of type "%s": %s', get_class($exception), $exception->getMessage());
    }

    /**
     * {@inheritdoc}
     */
    public function formatRequest(RequestInterface $request)
    {
        return $this->formatter->formatRequest($request);
    }

    public function formatResponseForRequest(ResponseInterface $response, RequestInterface $request)
    {
        if (method_exists($this->formatter, 'formatResponseForRequest')) {
            return $this->formatter->formatResponseForRequest($response, $request);
        }

        return $this->formatter->formatResponse($response);
    }

    public function formatResponse(ResponseInterface $response)
    {
        return $this->formatter->formatResponse($response);
    }

    /**
     * Format a RequestInterface as a cURL command.
     *
     * @return string
     */
    public function formatAsCurlCommand(RequestInterface $request)
    {
        return $this->curlFormatter->formatRequest($request);
    }
}
