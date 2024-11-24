<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Resources;

use Http\Client\Common\Plugin;
use Http\Promise\Promise;
use Psr\Http\Message\RequestInterface;

final class CustomPlugin implements Plugin
{
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function handleRequest(RequestInterface $request, callable $next, callable $first): Promise
    {
        return $next($request);
    }
}
