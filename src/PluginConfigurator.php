<?php

declare(strict_types=1);

namespace Http\HttplugBundle;

use Http\Client\Common\Plugin;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

interface PluginConfigurator
{
    public static function getConfigTreeBuilder(): TreeBuilder;

    /**
     * Creates the plugin with the given config.
     *
     * @param array<mixed> $config
     */
    public function create(array $config): Plugin;
}
