<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Collector;

/**
 * A Profile holds representation of what goes in an plugin (request) and what goes out (response and failure state).
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 *
 * @internal
 */
final class Profile
{
    private ?string $request = null;

    private ?string $response = null;

    private bool $failed = false;

    public function __construct(private readonly string $plugin)
    {
    }

    public function getPlugin(): string
    {
        return $this->plugin;
    }

    public function getRequest(): ?string
    {
        return $this->request;
    }

    public function setRequest(string $request): void
    {
        $this->request = $request;
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
}
