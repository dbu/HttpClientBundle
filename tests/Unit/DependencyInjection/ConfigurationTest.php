<?php

declare(strict_types=1);

namespace Http\HttplugBundle\Tests\Unit\DependencyInjection;

use Http\Adapter\Guzzle7\Client;
use Http\HttplugBundle\DependencyInjection\Configuration;
use Http\HttplugBundle\DependencyInjection\HttplugExtension;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * @author David Buchmann <mail@davidbu.ch>
 */
class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    private array $emptyConfig = [
        'default_client_autowiring' => true,
        'main_alias' => [
            'client' => 'httplug.client.default',
            'psr18_client' => 'httplug.psr18_client.default',
            'psr17_request_factory' => 'httplug.psr17_request_factory.default',
            'psr17_response_factory' => 'httplug.psr17_response_factory.default',
            'psr17_stream_factory' => 'httplug.psr17_stream_factory.default',
            'psr17_uri_factory' => 'httplug.psr17_uri_factory.default',
            'psr17_uploaded_file_factory' => 'httplug.psr17_uploaded_file_factory.default',
            'psr17_server_request_factory' => 'httplug.psr17_server_request_factory.default',
        ],
        'classes' => [
            'client' => null,
            'psr18_client' => null,
            'psr17_request_factory' => null,
            'psr17_response_factory' => null,
            'psr17_stream_factory' => null,
            'psr17_uri_factory' => null,
            'psr17_uploaded_file_factory' => null,
            'psr17_server_request_factory' => null,
        ],
        'clients' => [],
        'profiling' => [
            'enabled' => true,
            'formatter' => null,
            'captured_body_length' => 0,
        ],
        'plugins' => [
            'authentication' => [],
            'cache' => [
                'enabled' => false,
                'stream_factory' => 'httplug.psr17_stream_factory',
                'config' => [
                    'methods' => ['GET', 'HEAD'],
                    'blacklisted_paths' => [],
                    'cache_listeners' => [],
                ],
            ],
            'cookie' => [
                'enabled' => false,
            ],
            'decoder' => [
                'enabled' => true,
                'use_content_encoding' => true,
            ],
            'history' => [
                'enabled' => false,
            ],
            'logger' => [
                'enabled' => true,
                'logger' => 'logger',
                'formatter' => null,
            ],
            'redirect' => [
                'enabled' => true,
                'preserve_header' => true,
                'use_default_for_multiple' => true,
            ],
            'retry' => [
                'enabled' => true,
                'retry' => 1,
            ],
            'stopwatch' => [
                'enabled' => true,
                'stopwatch' => 'debug.stopwatch',
            ],
            'error' => [
                'enabled' => false,
                'only_server_exception' => false,
            ],
            'throttle' => [
                'enabled' => false,
                'key' => null,
                'tokens' => 1,
                'max_time' => null,
            ],
        ],
        'discovery' => [
            'client' => 'auto',
            'async_client' => null,
        ],
    ];

    protected function getContainerExtension(): ExtensionInterface
    {
        return new HttplugExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration(true);
    }

    public function testSupportsAllConfigFormats(): void
    {
        if (!class_exists(Client::class)) {
            $this->markTestSkipped('Guzzle 7 adapter is not installed');
        }

        $expectedConfiguration = [
            'default_client_autowiring' => false,
            'main_alias' => [
                'client' => 'my_client',
                'psr18_client' => 'httplug.psr18_client.default',
                'psr17_request_factory' => 'my_psr17_request_factory',
                'psr17_response_factory' => 'my_psr17_response_factory',
                'psr17_stream_factory' => 'my_psr17_stream_factory',
                'psr17_uri_factory' => 'my_psr17_uri_factory',
                'psr17_uploaded_file_factory' => 'httplug.psr17_uploaded_file_factory.default',
                'psr17_server_request_factory' => 'httplug.psr17_server_request_factory.default',
            ],
            'classes' => [
                'client' => Client::class,
                'psr18_client' => Client::class,
                'psr17_request_factory' => Psr17Factory::class,
                'psr17_response_factory' => Psr17Factory::class,
                'psr17_stream_factory' => Psr17Factory::class,
                'psr17_uri_factory' => Psr17Factory::class,
                'psr17_uploaded_file_factory' => Psr17Factory::class,
                'psr17_server_request_factory' => Psr17Factory::class,
            ],
            'clients' => [
                'test' => [
                    'factory' => 'httplug.factory.guzzle7',
                    'http_methods_client' => true,
                    'service' => null,
                    'public' => null,
                    'flexible_client' => false,
                    'batch_client' => false,
                    'plugins' => [
                        [
                            'reference' => [
                                'enabled' => true,
                                'id' => 'httplug.plugin.redirect',
                            ],
                        ],
                        [
                            'add_host' => [
                                'enabled' => true,
                                'host' => 'http://localhost',
                                'replace' => false,
                            ],
                        ],
                        [
                            'add_path' => [
                                'enabled' => true,
                                'path' => '/api/v1',
                            ],
                        ],
                        [
                            'base_uri' => [
                                'enabled' => true,
                                'uri' => 'http://localhost',
                                'replace' => false,
                            ],
                        ],
                        [
                            'content_type' => [
                                'enabled' => true,
                                'skip_detection' => true,
                            ],
                        ],
                        [
                            'header_set' => [
                                'enabled' => true,
                                'headers' => [
                                    'X-FOO' => 'bar',
                                ],
                            ],
                        ],
                        [
                            'header_remove' => [
                                'enabled' => true,
                                'headers' => [
                                    'X-FOO',
                                ],
                            ],
                        ],
                        [
                            'authentication' => [
                                'my_basic' => [
                                    'type' => 'basic',
                                    'username' => 'foo',
                                    'password' => 'bar',
                                    'params' => [],
                                ],
                            ],
                        ],
                    ],
                    'config' => [],
                ],
            ],
            'profiling' => [
                'enabled' => true,
                'formatter' => 'my_toolbar_formatter',
                'captured_body_length' => 0,
            ],
            'plugins' => [
                'authentication' => [
                    'my_basic' => [
                        'type' => 'basic',
                        'username' => 'foo',
                        'password' => 'bar',
                        'params' => [],
                    ],
                    'my_wsse' => [
                        'type' => 'wsse',
                        'username' => 'foo',
                        'password' => 'bar',
                        'params' => [],
                    ],
                    'my_bearer' => [
                        'type' => 'bearer',
                        'token' => 'foo',
                        'params' => [],
                    ],
                    'my_header' => [
                        'type' => 'header',
                        'header_name' => 'foo',
                        'header_value' => 'bar',
                        'params' => [],
                    ],
                    'my_service' => [
                        'type' => 'service',
                        'service' => 'my_auth_service',
                        'params' => [],
                    ],
                ],
                'cache' => [
                    'enabled' => true,
                    'cache_pool' => 'my_cache_pool',
                    'stream_factory' => 'my_other_stream_factory',
                    'config' => [
                        'cache_lifetime' => 2592000,
                        'default_ttl' => 42,
                        'hash_algo' => 'sha1',
                        'methods' => ['GET'],
                        'cache_key_generator' => null,
                        'respect_response_cache_directives' => ['X-Foo'],
                        'blacklisted_paths' => ['@/path/not-to-be/cached@'],
                        'cache_listeners' => [
                            'my_cache_listener_0',
                            'my_cache_listener_1',
                        ],
                    ],
                ],
                'cookie' => [
                    'enabled' => true,
                    'cookie_jar' => 'my_cookie_jar',
                ],
                'decoder' => [
                    'enabled' => false,
                    'use_content_encoding' => true,
                ],
                'history' => [
                    'enabled' => true,
                    'journal' => 'my_journal',
                ],
                'logger' => [
                    'enabled' => false,
                    'logger' => 'logger',
                    'formatter' => null,
                ],
                'redirect' => [
                    'enabled' => false,
                    'preserve_header' => true,
                    'use_default_for_multiple' => true,
                ],
                'retry' => [
                    'enabled' => false,
                    'retry' => 1,
                ],
                'stopwatch' => [
                    'enabled' => false,
                    'stopwatch' => 'debug.stopwatch',
                ],
                'error' => [
                    'enabled' => false,
                    'only_server_exception' => false,
                ],
                'throttle' => [
                    'enabled' => false,
                    'key' => null,
                    'tokens' => 1,
                    'max_time' => null,
                ],
            ],
            'discovery' => [
                'client' => 'auto',
                'async_client' => null,
            ],
        ];

        $formats = array_map(fn ($path) => __DIR__.'/../../Resources/Fixtures/'.$path, [
            'config/full.yml',
            'config/full.xml',
            'config/full.php',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($expectedConfiguration, [$format]);
        }
    }

    public function testMissingClass(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_class.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Nonexisting\Class');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testInvalidPlugin(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_plugin.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unrecognized option "foobar" under "httplug.clients.acme.plugins.0"');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testInvalidAuthentication(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_auth.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('password, service, username');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @group legacy
     */
    public function testInvalidCacheConfig(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_cache_config.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "httplug.plugins.cache.config": You can\'t provide config option "respect_cache_headers" and "respect_response_cache_directives" simultaneously. Use "respect_response_cache_directives" instead.');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    /**
     * @group legacy
     */
    public function testBackwardCompatibility(): void
    {
        $formats = array_map(fn ($path) => __DIR__.'/../../Resources/Fixtures/'.$path, [
            'config/bc/toolbar.yml',
            'config/bc/toolbar_auto.yml',
        ]);

        foreach ($formats as $format) {
            $this->assertProcessedConfigurationEquals($this->emptyConfig, [$format]);
        }
    }

    /**
     * @group legacy
     */
    public function testCacheConfigDeprecationCompatibility(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/cache_config.yml';
        $config = $this->emptyConfig;
        $config['plugins']['cache'] = array_merge($config['plugins']['cache'], [
            'enabled' => true,
            'cache_pool' => 'my_cache_pool',
            'config' => [
                'methods' => ['GET', 'HEAD'],
                'respect_cache_headers' => true,
                'blacklisted_paths' => [],
                'cache_listeners' => [],
            ],
        ]);
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }

    /**
     * @group legacy
     */
    public function testCacheConfigDeprecationCompatibilityIssue166(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/issue-166.yml';
        $config = $this->emptyConfig;
        $config['plugins']['cache'] = array_merge($config['plugins']['cache'], [
            'enabled' => true,
            'cache_pool' => 'my_cache_pool',
            'config' => [
                'methods' => ['GET', 'HEAD'],
                'respect_cache_headers' => false,
                'blacklisted_paths' => [],
                'cache_listeners' => [],
            ],
        ]);
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }

    public function testProfilingToolbarCollision(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/bc/profiling_toolbar.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Can\'t configure both "toolbar" and "profiling" section. The "toolbar" config is deprecated as of version 1.3.0, please only use "profiling".');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testClientCacheConfigMustHavePool(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/client_cache_config_with_no_pool.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('httplug.clients.test.plugins.0.cache');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testCacheConfigMustHavePool(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/cache_config_with_no_pool.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('cache_pool');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testLimitlessCapturedBodyLength(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/limitless_captured_body_length.yml';
        $config = $this->emptyConfig;
        $config['profiling']['captured_body_length'] = null;
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }

    public function testInvalidCapturedBodyLengthString(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/invalid_captured_body_length.yml';

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "captured_body_length" at path "httplug.profiling" must be an integer or null');
        $this->assertProcessedConfigurationEquals([], [$file]);
    }

    public function testNullDefaultTtl(): void
    {
        $file = __DIR__.'/../../Resources/Fixtures/config/ttl_null.yml';
        $config = $this->emptyConfig;
        $config['clients'] = [
            'test' => [
                'factory' => 'httplug.factory.auto',
                'service' => null,
                'public' => null,
                'flexible_client' => false,
                'http_methods_client' => false,
                'batch_client' => false,
                'config' => [],
                'plugins' => [
                    [
                        'cache' => [
                            'config' => [
                                'default_ttl' => null,
                                'blacklisted_paths' => [],
                                'methods' => ['GET', 'HEAD'],
                                'cache_listeners' => [],
                            ],
                            'cache_pool' => 'my_custom_cache_pull',
                            'enabled' => true,
                            'stream_factory' => 'httplug.psr17_stream_factory',
                        ],
                    ],
                ],
            ],
        ];
        $this->assertProcessedConfigurationEquals($config, [$file]);
    }
}
