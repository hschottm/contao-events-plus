# Contao Events Plus

Additional calendar event fields and optional Google Maps coordinate retrieval for **Contao 5.3**.

This focused successor extracts the event-field functionality from
[heimrichhannot/contao-events-bundle](https://github.com/heimrichhannot/contao-events-bundle).
It does not require the Utils Bundle and does not include sub-events, custom calendar
palette selection, list/reader integrations or frontend templates. Contao's existing
calendar features, including featured events and the location/address fields, remain available.

## Requirements

- PHP 8.2 or newer
- Contao 5.3 (Composer permits compatible Contao 5.x versions; 5.3 is the tested target)
- For automatic coordinates only: a Google Maps API key with access to the Geocoding API

## Installation

The package is available from this GitHub repository. Until it is listed on Packagist,
register the repository before requiring it:

```bash
composer config repositories.contao-events-plus vcs https://github.com/hschottm/contao-events-plus.git
composer require hschottm/contao-events-plus:^1.0
vendor/bin/contao-console contao:migrate
```

If necessary, clear the Contao cache. Release `1.0.0` targets Contao 5.3; the
`contao53` branch contains the corresponding development code.

## Event fields

The extension adds the following fields to `tl_calendar_events`, retaining the
original field names and SQL definitions for existing project data:

| Field | Purpose |
| --- | --- |
| `dateAdded` | Creation timestamp, populated on first save and reset for copies |
| `subTitle` | Subtitle |
| `shortTitle` | Short title |
| `street` | Street and house number |
| `postal` | Postal code |
| `city` | City |
| `coordinates` | Latitude and longitude, separated by a comma |
| `locationAdditional` | Additional location information, such as a meeting point |
| `website` | Website URL |

Editable fields appear in all four standard event source palettes (default,
internal page, article and external URL). `dateAdded` is managed automatically.
English and German labels and help texts are included. Your project can use these
fields through Contao's event models and its own templates.

## Google Maps API settings

In **System → Settings → Google Maps API**:

1. Enter a **Google Maps API key** and save the settings.
2. Activate **Enable automatic coordinate retrieval** and save again.
3. Save an event with a street, postal code and/or city and an empty coordinates field.

The checkbox is disabled until a non-empty key has been saved. Saving a key alone
does not activate retrieval. Removing the key disables retrieval; the key requirement
is also enforced on the server. A saved key is not a guarantee that Google accepts
it: enable the Geocoding API and configure the key's access and quota appropriately.
See [Google's Geocoding API documentation](https://developers.google.com/maps/documentation/geocoding/requests-geocoding).

The key uses the existing Contao setting `utilsGoogleApiKey`, so keys from the old
Events/Utils Bundle can be reused. The new setting
`eventsPlusEnableAutomaticCoordinates` defaults to disabled. The old negative
setting `skipCalendarEventCoordinateRetrieval` is not used.

Retrieval uses the address saved in the current event submission. Existing
coordinates, including manually entered ones, are never overwritten. Clear the
coordinates field and save to retrieve new coordinates after changing the address.
No request is sent while the feature is disabled, without a key, or for an empty address.

On API errors, the event remains saved and a backend message explains that retrieval
failed. No result leaves the field empty. Requests have a bounded timeout and errors
do not expose the key or request URL. Automatic retrieval sends the event address to Google.

## Migrating from contao-events-bundle

Back up the database and Composer configuration first. The two bundles cannot be
installed together because they define the same additional event fields.

```bash
composer config repositories.contao-events-plus vcs https://github.com/hschottm/contao-events-plus.git
composer remove heimrichhannot/contao-events-bundle --no-update
composer require hschottm/contao-events-plus:^1.0 --no-update
composer update hschottm/contao-events-plus heimrichhannot/contao-events-bundle --with-dependencies
vendor/bin/contao-console cache:clear
vendor/bin/contao-console contao:migrate
```

Existing values in the nine fields above are retained without a data conversion.
Automatic coordinate retrieval must be explicitly enabled after switching bundles,
even when a key already exists. Review schema changes; do not delete old sub-event
tables or other unused fields if their data is still needed.

Sub-event relations (`parentEvent`), `tl_calendar_sub_events`, custom calendar
palettes and the old list/reader classes are not provided by this package. Projects
using those features need an alternative before migrating.

## Development

```bash
composer update --no-plugins --no-scripts
composer --no-plugins test
```

PHPUnit covers the geocoding client, opt-in/key prerequisites, preserved coordinates,
saved-address retrieval and timestamps. An isolated Contao 5.3 kernel smoke test
checks fields, palettes, callback registration and translations without touching
an application database. GitHub Actions runs the checks on PHP 8.2 and 8.3.
Tests use mocked HTTP responses and do not send real Google requests.

## License and credits

Licensed under **LGPL-3.0-or-later**. See [LICENSE](LICENSE) for the GNU Lesser
General Public License v3 and [COPYING](COPYING) for the GNU General Public License
v3 incorporated by that license. See [NOTICE](NOTICE) for attribution.

Original field definitions and translations: Heimrich & Hannot GmbH and contributors.
Contao Events Plus adaptation and maintenance: Helmut Schottmüller.
