# Changelog

All notable changes to this project are documented here.

## [1.0.0] - 2026-11-06

Initial release for **Contao 5.3** (PHP 8.2+).

### Added

- Standalone `hschottm/contao-events-plus` package under LGPL-3.0-or-later.
- Event fields `dateAdded`, `subTitle`, `shortTitle`, `street`, `postal`, `city`, `coordinates`, `locationAdditional` and `website`, preserving the names and SQL definitions from contao-events-bundle.
- Additional fields in all four standard event source palettes, with English and German translations.
- Creation timestamps on first save and fresh timestamps when copying events.
- Google Maps API settings reusing `utilsGoogleApiKey` and a new **Enable automatic coordinate retrieval** checkbox, disabled by default and unavailable without a saved API key.
- Server-side key checks and automatic deactivation when the key is removed.
- Coordinate retrieval from the newly saved address, preserving existing coordinates and handling API errors, empty results and timeouts.
- Unit tests, an isolated Contao 5.3 kernel smoke test and GitHub Actions for PHP 8.2 and 8.3.
- Installation, configuration and migration documentation.

### Scope

- Derived from the event-field functionality of heimrichhannot/contao-events-bundle.
- No dependency on Utils Bundle; no sub-event, custom calendar palette or list/reader functionality.
- The legacy negative setting `skipCalendarEventCoordinateRetrieval` is replaced by explicit opt-in; automatic retrieval is not enabled during migration.

[1.0.0]: https://github.com/hschottm/contao-events-plus/releases/tag/1.0.0
