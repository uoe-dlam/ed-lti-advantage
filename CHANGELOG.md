# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Use extrenal tool to isolate the plugin Vendor libraries so the plugin still works without a clash with other Wordpress plugins (PR #)

### Fixed

- The latest version of NS Cloner no longer works with lti, so switch to using WP built methods for creating blogs (PR #15)
- Fixed $\_session('client-id') scoping to ensure that the DB lookup does not fail (PR #12)

## [1.0.1] - 2022-10-04

### Fixed

- Make sure blogs list page does not conflict with ed-lti plugin

## [1.0] - 2022-07-06

- First major release
