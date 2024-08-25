<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

/**
 * A Stack hold a collection of Profile to track the whole request execution.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Stack
{
    private string $client;

    private ?Stack $parent = null;

    /**
     * @var Profile[]
     */
    private array $profiles = [];

    private string $request;

    private ?string $response = null;

    private bool $failed = false;

    private ?string $requestTarget = null;

    private ?string $requestMethod = null;

    private ?string $requestHost = null;

    private ?string $requestScheme = null;

    private ?int $requestPort = null;

    private ?string $clientRequest = null;

    private ?string $clientResponse = null;

    private ?string $clientException = null;

    private ?int $responseCode = null;

    private int $duration = 0;

    private ?string $curlCommand = null;

    public function __construct(string $client, string $request)
    {
        $this->client = $client;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return Stack|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Stack $parent
     */
    public function setParent(self $parent)
    {
        $this->parent = $parent;
    }

    public function addProfile(Profile $profile)
    {
        $this->profiles[] = $profile;
    }

    /**
     * @return Profile[]
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return string|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * @return bool
     */
    public function isFailed()
    {
        return $this->failed;
    }

    /**
     * @param bool $failed
     */
    public function setFailed($failed)
    {
        $this->failed = $failed;
    }

    /**
     * @return string|null
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * @param string $requestTarget
     */
    public function setRequestTarget($requestTarget)
    {
        $this->requestTarget = $requestTarget;
    }

    /**
     * @return string|null
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }

    /**
     * @param string $requestMethod
     */
    public function setRequestMethod($requestMethod)
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * @return string|null
     */
    public function getClientRequest()
    {
        return $this->clientRequest;
    }

    /**
     * @param string $clientRequest
     */
    public function setClientRequest($clientRequest)
    {
        $this->clientRequest = $clientRequest;
    }

    /**
     * @return string|null
     */
    public function getClientResponse()
    {
        return $this->clientResponse;
    }

    /**
     * @param string $clientResponse
     */
    public function setClientResponse($clientResponse)
    {
        $this->clientResponse = $clientResponse;
    }

    /**
     * @return string|null
     */
    public function getClientException()
    {
        return $this->clientException;
    }

    /**
     * @param string $clientException
     */
    public function setClientException($clientException)
    {
        $this->clientException = $clientException;
    }

    /**
     * @return int|null
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @param int $responseCode
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @return string|null
     */
    public function getRequestHost()
    {
        return $this->requestHost;
    }

    /**
     * @param string $requestHost
     */
    public function setRequestHost($requestHost)
    {
        $this->requestHost = $requestHost;
    }

    /**
     * @return string|null
     */
    public function getRequestScheme()
    {
        return $this->requestScheme;
    }

    /**
     * @param string $requestScheme
     */
    public function setRequestScheme($requestScheme)
    {
        $this->requestScheme = $requestScheme;
    }

    public function getRequestPort(): ?int
    {
        return $this->requestPort;
    }

    public function setRequestPort(?int $port)
    {
        $this->requestPort = $port;
    }

    /**
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return string|null
     */
    public function getCurlCommand()
    {
        return $this->curlCommand;
    }

    /**
     * @param string $curlCommand
     */
    public function setCurlCommand($curlCommand)
    {
        $this->curlCommand = $curlCommand;
    }

    /**
     * @return string
     */
    public function getClientSlug()
    {
        return preg_replace('/[^a-zA-Z0-9_-]/u', '_', $this->client);
    }
}
