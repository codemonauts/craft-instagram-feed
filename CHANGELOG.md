# Instagram Feed Changelog

## Unreleased
### Changed
- Fixed background color of icon

### Fixed
- Add trailing slash to all requests
- Prevent empty user agent strings

## 1.0.5 - 2020-04-15
### Added
- Switch to write a dump file of the response from Instagram
- Switch to use a proxy (beta) 

### Changed
- Improve documentation

### Fixed
- HTTP headers send with file_get_contents

## 1.0.4 - 2019-09-15
### Fixed
- Settings in CP could not be saved due to a validation rule.
- Handle Instagram error response and return empty feed.
- Fixed timeout for file stream (float not microseconds).

## 1.0.3 - 2019-09-14
### Added
- Timestamps of the photos (thanks to @devotoare)
- Timeout fetching the Instgram page, default to 5 seconds, can be changed in the config file (thanks to @mhayes14)
- Support for hashtags (thanks to @JeroenOnstuimig)
- Function to return configured account name
- Guzzle http client
- Debugging Output (log level debug)

### Fixed
- Some typos (thanks to @ryanpcmcquen)

## 1.0.2 - 2019-05-20
### Added
- Captions of posts

## 1.0.1 - 2019-04-14
### Added
- Overwrite account name on function call
- Add some more logging

### Changed
- Cache account data by account name

## 1.0.0 - 2019-04-06
### Added
- Initial release
