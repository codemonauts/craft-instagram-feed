# Instagram Feed Changelog

## 1.0.7 - 2021-02-26
### Fixed
- Fixed videos without information about having an audio. 

## 1.0.6 - 2021-02-16
### Added
- Enable environment variables for setting username (thanks to @niektenhoopen)
- Add rel noopener and noreferrer properties to links in the README (thanks to @JayBox325)
- Add full image URL attribute.
- Add hasAudio attribute.

### Changed
- Prevent request storm if no items are cached and the request failed.
- Set default timeout for requests from 5 to 10 seconds.

### Fixed
- Fixed background color of icon
- Add trailing slash to all requests
- Prevent empty user agent strings
- Fixed some typos
- Catch all exceptions from Guzzle requests

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
