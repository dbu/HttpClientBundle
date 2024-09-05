# Change Log

The change log describes what is "Added", "Removed", "Changed" or "Fixed" between each release.

# Version 2

# 2.0.0 - (unreleased)

- Increased min PHP version to 8.1
- Removed the deprecated `httplug_markup` twig filter. Use `@Httplug/http_message.html.twig` template instead.
- Removed deprecated configuration options:
  - `toolbar`: Use `profiling` instead.
  - `respect_cache_headers`: Use `respect_response_cache_directives` instead.
- Removed the deprecated `Http\HttplugBundle\ClientFactory\PluginClientFactory`. Use `Http\Client\Common\PluginClientFactory` instead.
- Fixed a deprecation when creating a `HttpMethodsClient` via `http_methods_client: true`. Only PSR-17 factories are now passed as constructor arguments.
- Changed the default stream factory argument for the cache plugin. This now requires a PSR-17 StreamFactoryInterface instance.
- Creating a client using the `BuzzFactory` no longer accepts `verify_peer` and `verify_host` config options. Only a boolean `verify` flag is accepted, covering both previous options.
- Removed support of deprecated PHP-HTTP factories, only PSR-17 factories are now supported and used.
- Removed `message_factory`, `uri_factory`, and `stream_factory` classes config option. You can configure your own factories via psr17_*_factory classes config
- Removed support for guzzle5-adapter
- Removed support for Symfony versions <5.4
- Changed the return type of `ClientFactory` to return a `ClientInterface` instead of `ClientInterface|HttpClient`
- Changed the type of `httplug.client.default` to `ClientInterface` instead of `HttpClient`
- Removed the `DummyClient` interface
- Removed the `Http\Client\HttpClient` alias use the `Psr\Http\Client\ClientInterface` typehint in your services for autowiring.
- Changed classes marked as `@final` to be actually `final`. If you extended any of those, instead implement interfaces or decorate the class rather than extending it. Open an issue if you think a class needs to be made non-final to discuss what we should do.
- Added return type declaration to `Http\HttplugBundle\ClientFactory\ClientFactory::createClient`

# Version 1

# 1.34.3 - 2024-09-01

- Same as 1.34.2 but tagged on the 1.x banch instead of the feature branch.

# 1.34.2 - 2024-09-01

- More cleanup of the rate-limiter configuration. The service name is the full service name of the rate limiter, e.g. `limiter.my_name` when configuring `framework.rate_limiter.my_name`.

# 1.34.1 - 2024-09-01

- The rate-limiter name in the throttle plugin configuration is required.

# 1.34.0 - 2024-06-17

- Support to configure the throttle plugin.

# 1.33.1 - 2024-05-27

- Fixed extension to depend on the DependencyInjection component rather than the HttpKernel.

# 1.33.0 - 2024-02-27

- Support php-http/cache-plugin 2.0 and bump minimal version to 1.7 by defaulting the stream factory for cache to `httplug.psr17_stream_factory` (#448).

# 1.32.0 - 2023-12-06

- Added support for Symfony 7

# 1.31.0 - 2023-11-06

- Added configuration for the `header` authentication plugin (#437).

# 1.30.1 - 2023-09-07

- Added alias to allow autowiring the `AsyncHttpClient` interface (#436).

# 1.30.0 - 2023-08-31

- Adjusted phpdoc on ClientFactory to reflect that it usually returns a PSR ClientInterface.

# 1.29.1 - 2023-06-01

- Fixed deprecation from Symfony 6.3

# 1.29.0 - 2023-05-17

- Support PSR-18 ClientInterface in MockFactory.
- Removed support for php-http/httplug 1.

# 1.28.0 - 2023-05-12

- Added: Configure the seekable body plugins.
- Added: PHP 8.2 support.
- Added: Allow installation with PSR-7 `psr/http-message` 2.x.
- Added: alias to autowire `Psr\Http\Client\ClientInterface` service (#425).
- Deprecated `Http\Client\HttpClient` in favor of `Psr\Http\Client\ClientInterface` (#425).

# 1.27.1 - 2023-03-03

- Added `: void` to `Collector::reset` to avoid PHP warning.
- If `captured_body_length` is set to 0 (default value), show a special message rather than the
  generic message `This message has no captured body`.
- Fixed: Add slash in profiler if there is none between host and path.

# 1.27.0 - 2022-07-25

- Added support for configuring the error plugin via configuration.

# 1.26.2 - 2022-06-01

- Fixed: You can now configure the cache plugin option `cache_lifetime` to `null` (which makes the plugin not add to the maxAge).

# 1.26.1 - 2022-04-29

- Fixed: Setting the cache plugin option `respect_response_cache_directives` to `null` makes the
  plugin use the default set of directives instead of triggering an error.

# 1.26.0 - 2022-03-17

- Fixed you can now configure the cache plugin `default_ttl` with `null`.

# 1.25.0 - 2021-11-26
- Added PHP 8.1 support
- Added Symfony 6 support
- Removed Symfony 3.x support

# 1.24.0 - 2021-10-23
- Changed stopwatch category from default to "httplug", so it's more prominent on Execution timeline view
- Changed tab texts inside profiler so that it shows ports in URL in case it's non-standard
- Changed default logging plugin monolog channel from "app" to "httplug"
- Fixed compatibility with Twig 3.x

# 1.23.1 - 2021-10-13
- Fixed issue with whitespaces in URL when URL in tab was copied
- Fixed dark mode compatiblity, making some previously invisible elements visible

# 1.23.0 - 2021-08-30

- Changed the way request/response body is displayed in profiler. symfony/var-dumper is used now.
- Changed badge counter of # of requests on side menu to be always visible

# 1.22.1 - 2021-07-26

- Do not deprecate the service alias for the old Http\Client\HttpClient interface because different Symfony
  versions expect a different syntax for the deprecation, which triggers an error on some Symfony versions.

# 1.22.0 - 2021-07-26

- Register client as alias for the PSR Psr\Http\Client\ClientInterface
- Deprecate relying on the Http\Client\HttpClient interface in favor of the PSR ClientInterface

# 1.21.0 - 2021-07-10

- Added support for [Target attribute](https://symfony.com/blog/new-in-symfony-5-3-service-autowiring-with-attributes)
  available in Symfony 5.3

# 1.20.1 - 2021-02-12

- Added `kernel.reset` tag to the Collector.

# 1.20.0 - 2020-12-23

- Support PHP 8, dropped support for PHP 7.2.

## 1.19.0 - 2020-10-21

### Changed

- `ConfiguredClientsStrategy` no longer implements `EventSubscriberInterface`,
  this has been moved to `ConfiguredClientsStrategyListener` to avoid initializing
  the strategy on every request.

## 1.18.0 - 2020-03-30

### Added

- Support the symfony/http-client
- Register PSR-17 / PSR-18 classes as services
- Allow to configure cache listeners on the cache plugin

### Changed

- Tests are excluded from zip releases
- Define plugin service templates as abstract to avoid warnings from Symfony

## 1.17.0 - 2019-12-27

### Added

- Support Symfony 5 and Twig 3.
- Configured clients are now tagged with `'httplug.client'`
- Adds a link to profiler page when response is from a Symfony application with
  profiler enabled
- Adding `blacklisted_paths` option of `php-http/cache-plugin`

### Changed

- Fixed error handling. Now TypeErrors and other \Throwable are correctly handled by ProfileClient
- Use tabular design in profiler for HTTP request/response headers

### Deprecated

- `httplug.collector.twig.http_message` service
- `httplug_markup` Twig function

## 1.16.0 - 2019-06-05

### Changed

- Dropped support for PHP < 7.1
- Dropped support for Symfony versions that do not receive bugfixes

### Added

- Integration for VCR Plugin

### Fixed

- Fix compatibility with curl-client `2.*`. The `CurlFactory` now builds the
  client using PSR-17 factories. Marked a conflict for curl-client `1.*`.

## 1.15.2 - 2019-04-18

### Fixed

- Fix to pass only allowed options to the `ContentTypePlugin`.

## 1.15.1 - 2019-04-12

### Fixed

- Fix undefined `$serviceId` when a client is named "default".

## 1.15.0 - 2019-03-29

### Added

- Autowiring support for FlexibleClient, HttpMethodsClientInterface and
  BatchClientInterface if they are enabled on the default/first client.
  (Only available with Httplug 2)
- Configuration for the `content_type` plugin
- Support for namespaced Twig classes.
- Configuration option `default_client_autowiring` that you can set to false
  to prevent autowiring the HttpClient and HttpAsyncClient

### Changed

- Moved source code to `src/` and tests to `tests/`
- Removed `twig/twig` dependency
- Removed hard dependency on `php-http/cache-plugin`. If you want to use the
  cache plugin, you need to require it in your project.
- Allow to set `httpplug.profiling.captured_body_length` configuration to `null`
  to avoid body limitation size.

### Fixed

- MockFactory now accepts any client, e.g. a mock client decorated with the
  plugin client for the development panel, so that configuring a mock client
  actually works. The MockFactory is now `final`.
- Width of the HTTPlug icon in Profiler Toolbar.
- `RequestExceptionInterface` formatting (`getResponse()` is not called anymore).

## 1.14.0

### Added

- Support for any PSR-18 client.

### Changed

- Allow version 2 of `php-http/client-common`

### Fixed

- Fix deprecated notice when using symfony/config > 4.2
- Profiler does not display stack when client name contains dots

## 1.13.1 - 2018-11-28

### Fixed

- Fix wrong duration calculation for asynchronous calls

## 1.13.0 - 2018-11-13

### Added

- Allow to configure the `QueryDefaultsPlugin`

## 1.12.0 - 2018-10-25

### Added

- Add configuration option to allow making client services public if needed

## 1.11.0 - 2018-07-07

### Added

- Add support for QueryParam in the AuthenticationPlugin

### Fixed

- Deprecation warnings on Symfony 4.1

## 1.10.0 - 2018-03-27

### Added

- Allow to configure the `AddPathPlugin` per client, under the `add_path` configuration key.
- Allow to configure clients with a `service` instead of a factory.

## 1.9.0 - 2018-03-06

### Added

- Allow to configure the `BaseUriPlugin` per client, under the `base_uri` configuration key.

## 1.8.1 - 2017-12-06

### Fixed

- `PluginClientFactory` name conflict with PHP 5.x.

## 1.8.0 - 2017-11-30

### Added

- Symfony 4 support.
- Support autowiring of `Http\Client\Common\PluginClientFactory`.
- Any third party library using `Http\Client\Common\PluginClientFactory` to create `Http\Client\Common\PluginClient`
instances now gets zero config profiling.
- `Http\HttplugBundle\Collector\Collector::reset()`

### Changed

- `ProfilePlugin` and `StackPlugin` are no longer registered as (private) services decorators. Those decorators are now
created through the `Http\HttplugBundle\Collector\PluginClientFactory`.

### Deprecated

- The `Http\HttplugBundle\ClientFactory\PluginClientFactory` class.

### Fixed

- Removed wrapping auto discovered clients in a `PluginClient`, prevent double profiling.
- Added missing service reference for `CachePlugin`'s `cache_key_generator` configuration option so that the option now actually works.

## 1.7.1 - 2017-08-04

### Fixed

- Display of the profiler panel when used in a symfony/flex project.

## 1.7.0 - 2017-07-25

### Added

- Add `Http\Message\MessageFactory`, `Http\Message\StreamFactory`, `Http\Message\UriFactory` and `Http\Client\HttpAsyncClient`
services aliases for autowiring in Symfony 3.3.
- Redirect requests are now displayed as nested.

### Changed

- Plugins are now displayed with their FQCN instead of service id.

### Deprecated

- The `DummyClient` interface.

## 1.6.0 - 2017-05-22

### Added

- Made the "factory" configuration key optional.

### Changed

- We do collect profiler data after the request is processed by a plugin. With this change we
will for example see the changes of `HeaderAppendPlugin` at that plugin instead of the next one.

## 1.5.0 - 2017-05-05

### Added

- The real request method and target url are now displayed in the profiler.
- Support the cache plugin configuration for `respect_response_cache_directives`.
- Extended WebProfilerToolbar item to list request with details.
- You can now copy any request as cURL command in the profiler.
- Support for autowring in Symfony 3.3
- Improved configuration validation

### Changed

- The profiler design has been updated.
- Removed stopwatch-plugin in favor of `ProfileClient`.

### Deprecated

- The configuration option `cache.config.respect_cache_headers` should no longer be used. Use `cache.config.respect_response_cache_directives` instead.

## 1.4.1 - 2017-02-23

### Fixed

- Make sure we always have a stack

## 1.4.0 - 2017-02-21

### Changed

- The profiler collector has been rewritten to use objects instead of arrays.
- Bumped minimum Symfony version to 2.8.
- Updated profiler badges to match Symfony 2.8+ profiler design.

### Fixed

- WebProfiler is no longer broken when there was a redirect.
- Dummy client should provide methods of HttpClient and HttpAsyncClient

## 1.3.0 - 2016-08-16

### Added

- You can now also configure client specific plugins in the `plugins` option of a client.
- Some plugins that you previously had to define in your own services can now be configured on the client.
- Support for BatchClient
- The stopwatch plugin included by default when using profiling.

### Changed

- All clients are registered with the PluginClient (even in production)
- Improved debug tool registration

### Deprecated

- `auto` value in `toolbar.enabled` config
- `toolbar` config, use `profiling` instead

### Fixed

- Decoder, Redirect and Retry plugins can now be used and no longer trigger an error because of incorrect constructor arguments.


## 1.2.2 - 2016-07-19

### Fixed

- Do not register debug tools when debugging is disabled (eg. in prod mode)


## 1.2.1 - 2016-07-19

### Fixed

- Auto discovery by using the appropriate discovery class


## 1.2.0 - 2016-07-18

### Added

- Support for cRUL constant names in configuration
- Flexible and HTTP Methods client support
- Discovery strategy to discover configured clients and/or add profiling

### Changed

- Improved collector to include plugin stack in profile data


## 1.1.1 - 2016-06-30

### Changed

- Removed Puli logic and require `php-http/discovery:0.9` which makes Puli optional.


## 1.1.0 - 2016-05-19

### Added

- Client factories for Buzz.

### Changed

- Guzzle 6 client is now created according to the Httplug specifications with automated minimal behaviour.
  Make sure you configure the Httplug plugins as needed,
  for example if you want to get exceptions for failure HTTP status codes.
- **[BC] PluginClientFactory returns an instance of `Http\Client\Common\PluginClient`** (see [php-http/client-common#14](https://github.com/php-http/client-common/pull/14))
- Plugins are loaded from their new packages

### Fixed

- Puli autoload issue on >=PHP 5.6, see [puli/issues#190](https://github.com/puli/issues/issues/190)


## 1.0.0 - 2016-03-04

### Added

- New configuration for authentication plugin. You may now specify the authentication credentials directly in the bundle's configuration. This will break previous authentication configuration.
- Client factories for Curl, React and Socket.

### Fixed

- Bug with circular reference when a client was named 'default'

### Removed

- Dependency on `php-http/discovery`. You must now install `puli/symfony-bundle` to use auto discovery.


## 0.2.0 - 2016-02-23

## 0.1.0 - 2016-01-11
