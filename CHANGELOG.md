# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2022-10-04

### Changed

- Use external tool to isolate the plugin Vendor libraries so the plugin still works without a clash with other Wordpress plugins (PR #17)

### Fixed

- The latest version of NS Cloner no longer works with lti, so switch to using WP built methods for creating blogs (PR #15)
- Fixed $\_session('client-id') scoping to ensure that the DB lookup does not fail (PR #12)

## [1.0.1] - 2022-10-04

### Fixed

- Make sure blogs list page does not conflict with ed-lti plugin

## [1.0] - 2022-07-06

- First major release

[Unreleased]: https://github.com/uoe-dlam/ed-lti-advantage/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/uoe-dlam/ed-lti-advantage/compare/tag/v1.1.0...v1.0.1
[1.0.1]: https://github.com/uoe-dlam/ed-lti-advantage/compare/tag/v1.0.1...v1.0
[1.0]: https://github.com/uoe-dlam/ed-lti-advantage/releases/tag/v1.0
