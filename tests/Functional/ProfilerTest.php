<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfilerTest extends WebTestCase
{
    /**
     * @group legacy
     */
    public function testShowProfiler(): void
    {
        $client = static::createClient();
        $httpClient = $client->getContainer()->get('httplug.client.acme');

        assert($httpClient instanceof ClientInterface);

        $httpClient->sendRequest(new Request('GET', '/posts/1'));

        // Browse any page to get a profile
        $client->request('GET', '/');

        $client->request('GET', '/_profiler/latest?panel=httplug');
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString(<<<HTML
        <span class="httplug-host">jsonplaceholder.typicode.com</span>
        HTML, $content);
    }
}
