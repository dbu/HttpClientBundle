<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Discovery;

use Http\Discovery\Psr18ClientDiscovery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
final class ConfiguredClientsStrategyListener implements EventSubscriberInterface
{
    /**
     * Make sure to use the custom strategy.
     */
    public function onEvent(): void
    {
        Psr18ClientDiscovery::prependStrategy(ConfiguredClientsStrategy::class);
    }

    /**
     * Whenever these events occur we make sure to add the custom strategy to the discovery.
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => ['onEvent', 1024],
            'console.command' => ['onEvent', 1024],
        ];
    }
}
