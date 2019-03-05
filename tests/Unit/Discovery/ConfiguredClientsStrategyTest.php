<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\Discovery;

use Http\Client\HttpAsyncClient;
use Http\HttplugBundle\Discovery\ConfiguredClientsStrategy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

final class ConfiguredClientsStrategyTest extends TestCase
{
    public function testGetCandidates(): void
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $httpAsyncClient = $this->getMockBuilder(HttpAsyncClient::class)->getMock();
        $strategy = new ConfiguredClientsStrategy($httpClient, $httpAsyncClient);

        $candidates = $strategy::getCandidates(ClientInterface::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }

    public function testGetCandidatesEmpty(): void
    {
        $strategy = new ConfiguredClientsStrategy(null, null);

        $candidates = $strategy::getCandidates(ClientInterface::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }

    public function testGetCandidatesEmptyAsync(): void
    {
        $httpClient = $this->getMockBuilder(ClientInterface::class)->getMock();
        $strategy = new ConfiguredClientsStrategy($httpClient, null);

        $candidates = $strategy::getCandidates(ClientInterface::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpClient, $candidate['class']());

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $this->assertEquals([], $candidates);
    }

    public function testGetCandidatesEmptySync(): void
    {
        $httpAsyncClient = $this->getMockBuilder(HttpAsyncClient::class)->getMock();
        $strategy = new ConfiguredClientsStrategy(null, $httpAsyncClient);

        $candidates = $strategy::getCandidates(ClientInterface::class);
        $this->assertEquals([], $candidates);

        $candidates = $strategy::getCandidates(HttpAsyncClient::class);
        $candidate = array_shift($candidates);
        $this->assertEquals($httpAsyncClient, $candidate['class']());
    }
}
