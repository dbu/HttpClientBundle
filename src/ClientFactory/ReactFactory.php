<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Http\Adapter\React\Client;
use Http\Message\MessageFactory;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 *
 * @final
 */
class ReactFactory implements ClientFactory
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
        if (!class_exists('Http\Adapter\React\Client')) {
            throw new \LogicException('To use the React adapter you need to install the "php-http/react-adapter" package.');
        }

        return new Client($this->messageFactory);
    }
}
