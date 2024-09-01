<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Client\Socket\Client;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class SocketFactory implements ClientFactory
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
        if (!class_exists('Http\Client\Socket\Client')) {
            throw new \LogicException('To use the Socket client you need to install the "php-http/socket-client" package.');
        }

        return new Client($config);
    }
}
