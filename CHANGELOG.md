# Change Log

## 1.4.0 - 2017-XX-XX

### Changed

- The profiler collector has been rewritten to use objects instead of arrays.

### Fixed

- WebProfiler is no longer broken when there was a redirect.

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
