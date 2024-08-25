<?php

declare(strict_types=1);

namespace Http\HttplugBundle\ClientFactory;

use Buzz\Client\FileGetContents;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BuzzFactory implements ClientFactory
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createClient(array $config = [])
    {
        if (!class_exists('Buzz\Client\FileGetContents')) {
            throw new \LogicException('To use the Buzz you need to install the "kriswallsmith/buzz" package.');
        }

        return new FileGetContents($this->responseFactory, $this->getOptions($config));
    }

    /**
     * Get options to configure the Buzz client.
     */
    private function getOptions(array $config = [])
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
          'timeout' => 5,
          'verify' => true,
          'proxy' => null,
        ]);

        $resolver->setAllowedTypes('timeout', 'int');
        $resolver->setAllowedTypes('verify', 'bool');
        $resolver->setAllowedTypes('proxy', ['string', 'null']);

        return $resolver->resolve($config);
    }
}
