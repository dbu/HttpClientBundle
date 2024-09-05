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
    private ?Stack $parent = null;

    /**
     * @var Profile[]
     */
    private array $profiles = [];

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

    public function __construct(
        private readonly string $client,
        private readonly string $request,
    ) {
    }

    public function getClient(): string
    {
        return $this->client;
    }

    public function getParent(): ?Stack
    {
        return $this->parent;
    }

    public function setParent(self $parent): void
    {
        $this->parent = $parent;
    }

    public function addProfile(Profile $profile): void
    {
        $this->profiles[] = $profile;
    }

    /**
     * @return Profile[]
     */
    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function getRequest(): string
    {
        return $this->request;
    }

    public function getResponse(): ?string
    {
        return $this->response;
    }

    public function setResponse(string $response): void
    {
        $this->response = $response;
    }

    public function isFailed(): bool
    {
        return $this->failed;
    }

    public function setFailed(bool $failed): void
    {
        $this->failed = $failed;
    }

    public function getRequestTarget(): ?string
    {
        return $this->requestTarget;
    }

    public function setRequestTarget(string $requestTarget): void
    {
        $this->requestTarget = $requestTarget;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function setRequestMethod(string $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }

    public function getClientRequest(): ?string
    {
        return $this->clientRequest;
    }

    public function setClientRequest(string $clientRequest): void
    {
        $this->clientRequest = $clientRequest;
    }

    public function getClientResponse(): ?string
    {
        return $this->clientResponse;
    }

    public function setClientResponse(string $clientResponse): void
    {
        $this->clientResponse = $clientResponse;
    }

    public function getClientException(): ?string
    {
        return $this->clientException;
    }

    public function setClientException(string $clientException): void
    {
        $this->clientException = $clientException;
    }

    public function getResponseCode(): ?int
    {
        return $this->responseCode;
    }

    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    public function getRequestHost(): ?string
    {
        return $this->requestHost;
    }

    public function setRequestHost(string $requestHost): void
    {
        $this->requestHost = $requestHost;
    }

    public function getRequestScheme(): ?string
    {
        return $this->requestScheme;
    }

    public function setRequestScheme(string $requestScheme): void
    {
        $this->requestScheme = $requestScheme;
    }

    public function getRequestPort(): ?int
    {
        return $this->requestPort;
    }

    public function setRequestPort(?int $port): void
    {
        $this->requestPort = $port;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getCurlCommand(): ?string
    {
        return $this->curlCommand;
    }

    public function setCurlCommand(string $curlCommand): void
    {
        $this->curlCommand = $curlCommand;
    }

    public function getClientSlug(): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/u', '_', $this->client);
    }
}
