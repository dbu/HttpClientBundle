<?php

declare(strict_types=1);

$container->loadFromExtension('httplug', [
    'default_client_autowiring' => false,
    'main_alias' => [
        'client' => 'my_client',
        'psr17_request_factory' => 'my_psr17_request_factory',
        'psr17_response_factory' => 'my_psr17_response_factory',
        'psr17_uri_factory' => 'my_psr17_uri_factory',
        'psr17_stream_factory' => 'my_psr17_stream_factory',
    ],
    'classes' => [
        'client' => Http\Adapter\Guzzle7\Client::class,
        'psr18_client' => Http\Adapter\Guzzle7\Client::class,
        'psr17_request_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
        'psr17_response_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
        'psr17_stream_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
        'psr17_uri_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
        'psr17_uploaded_file_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
        'psr17_server_request_factory' => Nyholm\Psr7\Factory\Psr17Factory::class,
    ],
    'clients' => [
        'test' => [
            'factory' => 'httplug.factory.guzzle7',
            'http_methods_client' => true,
            'plugins' => [
                'httplug.plugin.redirect',
                [
                    'add_host' => [
                        'host' => 'http://localhost',
                    ],
                ],
                [
                    'add_path' => [
                        'path' => '/api/v1',
                    ],
                ],
                [
                    'base_uri' => [
                        'uri' => 'http://localhost',
                    ],
                ],
                [
                    'content_type' => [
                        'skip_detection' => true,
                    ],
                ],
                [
                    'header_set' => [
                        'headers' => [
                            'X-FOO' => 'bar',
                        ],
                    ],
                ],
                [
                    'header_remove' => [
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
                        ],
                    ],
                ],
            ],
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
            ],
            'my_wsse' => [
                'type' => 'wsse',
                'username' => 'foo',
                'password' => 'bar',
            ],
            'my_bearer' => [
                'type' => 'bearer',
                'token' => 'foo',
            ],
            'my_header' => [
                'type' => 'header',
                'header_name' => 'foo',
                'header_value' => 'bar',
            ],
            'my_service' => [
                'type' => 'service',
                'service' => 'my_auth_service',
            ],
        ],
        'cache' => [
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
            'cookie_jar' => 'my_cookie_jar',
        ],
        'decoder' => [
            'enabled' => false,
        ],
        'history' => [
            'journal' => 'my_journal',
        ],
        'logger' => [
            'enabled' => false,
        ],
        'redirect' => [
            'enabled' => false,
        ],
        'retry' => [
            'enabled' => false,
        ],
        'stopwatch' => [
            'enabled' => false,
        ],
    ],
]);
