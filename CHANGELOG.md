# Release Notes for Instagram Feed Plugin

## 2.2.0 - 2024-01-04

> {note} Instagram has changed the data structure. If you no longer get a feed, this update should fix the problem.

### Changed

- Adaptation to the recent changes in the data structure of Instagram.

## 2.1.0 - 2022-10-21

### Added

- Set referrer in proxy requests.
- Set plugin version in proxy requests.
- New tag structure v3.

### Changed

- Refactored parsers to generalize it more and make it easier to adapt to new structures in the future. 

## 2.0.1 - 2022-06-03 [CRITICAL]

> {note} Instagram has changed the data structure on 06/01/2022. Without this update, the plugin will no longer work.

### Changed

- Adaptation to the changes in the data structure of Instagram on 06/01/2022.

## 2.0.0 - 2022-04-29

### Added

- Craft CMS 4 compatibility
- Option to cache invalidate Instagram data only
- Settings page indicates if a setting is overwritten by config file

### Changed

- Requires Craft CMS >= 4.0 and php >= 8.0
- If an image could not be fetched, the post is not displayed anymore

### Fixed

- Input validation for shortcode. (#59)
- Missing error message when saving empty account name in settings
- Validate Instagram account name

### Deleted

- Remove config setting for individual user agent

## 1.1.7 - 2021-07-19

### Changed

- Add PHP 8 to composer version constraint.

## 1.1.6 - 2021-07-16

### Changed

- Removed file_get_contents() and always use Guzzle for all requests.
- Changed link to new documentation.

## 1.1.5 - 2021-06-16

### Fixed

- If you use a volume to store the images, the first time displaying the images failed.
- In a scenario, where the image files already exists on the volume but without an asset entry in Craft, the files on the volume will be deleted and downloaded again.

## 1.1.4 - 2021-06-11

### Fixed

- In some cases, you get the old tag structure. So we accept both now.

## 1.1.3 - 2021-06-09

### Fixed

- Instagram has changed the structure of the tag data. We have updated the code accordingly.

## 1.1.2 - 2021-05-12

### Changed

- We made the plugin compatible to older Craft versions again. Changed the required version back to >= 3.0.0.

## 1.1.1 - 2021-04-29

### Fixed

- We now require Craft >= 3.5.0.

## 1.1.0 - 2021-04-29

> {note} Since the end of April 2021, Instagram sets the "cross-origin-resource-policy" header to "same-origin" to all their images, which means that your browser is not allowed to load the images inside another website which is not "instagram.com". Starting with this release of the plugin we download, store and serve the images locally. This may have an impact on your website, and you should read the sections "[Local storage](https://github.com/codemonauts/craft-instagram-feed#local-storage)" and "[Blocked requests](https://github.com/codemonauts/craft-instagram-feed#blocked-requests)" carefully.

### Added

- Downloading and storing the Instagram images locally to either Craft's storage path or a volume and path you can configure.
- New keys `imageSource` and `thumbnailSource` in the array of posts, that hold the original image URLs from Instagram.
- New key `asset` in the array of posts, that is an asset element of the Instagram image stored on a volume (only available when using a volume to store the image).

### Changed

- The keys `src`, `thumbnail` and `image` in the array of posts now have URLs pointing to your local copy of the images, which the plugin has downloaded and stored on your server.

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
