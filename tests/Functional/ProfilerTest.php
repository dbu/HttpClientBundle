<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Functional;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfilerTest extends WebTestCase
{
    /**
     * @group legacy
     */
    public function testShowProfiler(): void
    {
        $client = static::createClient();
        $httpClient = $client->getContainer()->get('httplug.client.acme');

        assert($httpClient instanceof HttpClient);

        $httpClient->sendRequest(new Request('GET', '/posts/1'));

        //Browse any page to get a profile
        $client->request('GET', '/');

        $client->request('GET', '/_profiler/latest?panel=httplug');
        $content = $client->getResponse()->getContent();
        $this->assertStringContainsString(<<<HTML
        <div class="label httplug-stack-header-target"><span class="httplug-scheme">https://</span><span class="httplug-host">jsonplaceholder.typicode.com</span><span class="httplug-target">/posts/1</span></div>
        HTML, str_replace(" ", "", $content));
    }
}
