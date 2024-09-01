<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use GuzzleHttp\Client;
use Http\Adapter\Guzzle5\Client as Adapter;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class Guzzle5Factory implements ClientFactory
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    public function __construct(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists('Http\Adapter\Guzzle5\Client')) {
            throw new \LogicException('To use the Guzzle5 adapter you need to install the "php-http/guzzle5-adapter" package.');
        }

        $client = new Client($config);

        return new Adapter($client, $this->messageFactory);
    }
}
