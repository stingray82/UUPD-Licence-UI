# UUPD License UI

Reusable vendor-aware license activation and status UI for WordPress plugins that use the UUPD licensing and updater flow.

This package is intended to be bundled inside a plugin alongside UUPD 2.0. It provides a standalone license page, an optional inline license box, wp-config.php license-key support, live activation/deactivation/check calls, and vendor-scoped updater integration.

This Licence class is designed to work with [UUPD](https://uupd.uk)



## UUPD 2.0 compatibility

UUPD 2.0 uses a vendor-aware identity model. Every updater instance is identified by both:

```php
vendor + slug
```

This project follows that model and does not implement old slug-only compatibility paths.

Important UUPD 2.0 assumptions:

- `vendor` is required.
- `slug` is required.
- Scoped filters use `uupd/<filter>/<vendor>/<slug>`.
- Cache keys use the `uupd_<vendor>__<slug>` pattern.
- Slug-only filters from UUPD V1 are not supported.
- Debug logging can still be enabled with the `updater_enable_debug` filter.
- Structured logging can be observed through the `uupd/log` action.

UUPD 2.0 release notes: <https://github.com/stingray82/UUPD/releases>

## Features

- Standalone WordPress admin license page.
- Compact inline license box for plugin settings pages.
- Vendor + slug scoped option names, menu slugs, cron hooks, and cache prefixes.
- License keys can be entered in the admin UI or managed via wp-config.php.
- wp-config.php-managed licenses make the UI read-only and do not store the raw key in `wp_options`.
- Calls UUPD Server license endpoints for activation, deactivation, and status checks.
- Injects the active license key into the vendor-aware UUPD updater config.
- Flushes updater transients after license changes.
- Supports opportunistic admin sync and cron-based license checks.
- Supports admin notices for missing or inactive licenses.

## Requirements

- WordPress plugin environment.
- UUPD 2.0-compatible updater registration.
- A UUPD license server exposing the license REST endpoints.
- PHP version compatible with your plugin and UUPD bundle.

## File placement

A common layout is:

```text
example-plugin/
├── example-plugin.php
├── includes/
│   ├── uupd/
│   │   └── updater.php
│   └── license/
│       └── class-uupd-license-ui.php
└── README.md
```

Load the updater first, then load and register the license UI.

```php
require_once __DIR__ . '/includes/uupd/updater.php';
require_once __DIR__ . '/includes/license/class-uupd-license-ui.php';
```

## Basic setup

Register the license UI with the same `vendor` and `slug` used by your UUPD updater registration.

```php
use UUPD\UI\License\V1\UUPD_License_UI;

add_action( 'plugins_loaded', function () {
    UUPD_License_UI::register( [
        'vendor'         => 'tdlab',
        'slug'           => 'example-plugin',
        'item_id'        => 7,
        'plugin_name'    => 'Example Plugin',
        'license_server' => 'https://updates.example.com',
        'metadata_base'  => 'https://updates.example.com',
    ] );
} );
```

Minimum required config:

```php
[
    'vendor'         => 'tdlab',
    'slug'           => 'example-plugin',
    'item_id'        => 7,
    'license_server' => 'https://updates.example.com',
    'metadata_base'  => 'https://updates.example.com',
]
```

## Updater integration

This UI hooks into UUPD 2.0 vendor-scoped filters:

```text
uupd/server_url/{vendor}/{slug}
uupd/filter_config/{vendor}/{slug}
```

The injected updater config includes:

- the configured `metadata_base` or `license_server` as the updater server URL;
- the resolved license key only when the stored license status is `active`.

Your updater registration should use the same `vendor` and `slug` values.

Example updater-style config:

```php
$uupd_config = [
    'vendor' => 'tdlab',
    'slug'   => 'example-plugin',
    'server' => 'https://updates.example.com',
];
```

Exact updater bootstrap code depends on how you bundle UUPD in your plugin.

## License server endpoints

The license UI posts JSON requests to:

```text
POST /wp-json/uupd/v1/license/activate
POST /wp-json/uupd/v1/license/deactivate
POST /wp-json/uupd/v1/license/check
```

Payload shape:

```json
{
  "license_key": "XXXX-XXXX-XXXX-XXXX",
  "slug": "example-plugin",
  "domain": "https://example.com"
}
```

The class stores status, timestamps, last response data, and last error data in a vendor-scoped option.

Default option name:

```text
uupd_license_{vendor}__{slug}
```

Example:

```text
uupd_license_tdlab__example_plugin
```

## wp-config.php-managed licenses

By default, the class looks for a constant named:

```text
UUPD_{VENDOR}_{SLUG}_LICENSE_KEY
```

Example:

```php
define( 'UUPD_TDLAB_EXAMPLE_PLUGIN_LICENSE_KEY', 'XXXX-XXXX-XXXX-XXXX' );
```

When this constant is present:

- it takes precedence over the stored database value by default;
- the admin UI becomes read-only;
- activation/deactivation buttons are hidden;
- the raw license key is not persisted in `wp_options`;
- admin nags are suppressed while a key is present;
- the updater still receives the resolved key through the vendor-scoped config filter.

You can override the constant name:

```php
UUPD_License_UI::register( [
    'vendor'           => 'tdlab',
    'slug'             => 'example-plugin',
    'item_id'          => 7,
    'license_server'   => 'https://updates.example.com',
    'metadata_base'    => 'https://updates.example.com',
    'license_constant' => 'MY_CUSTOM_LICENSE_KEY',
] );
```

You can also let the database value win by setting:

```php
'prefer_constant' => false,
```

## Rendering an inline license box

Use the inline box on your own settings screen after the instance has been registered.

```php
use UUPD\UI\License\V1\UUPD_License_UI;

UUPD_License_UI::render_box_for( 'example-plugin', 'tdlab', [
    'title'       => 'Example Plugin License',
    'description' => 'Enter your license key to enable updates.',
] );
```

## Admin menu options

By default, the license page is added under **Settings → License**.

Useful menu config:

```php
[
    'menu_parent' => 'options-general.php',
    'menu_title'  => 'License',
    'page_title'  => 'Example Plugin License',
    'capability'  => 'manage_options',
]
```

To disable the standalone menu and use only the inline box:

```php
'menu_parent' => false,
```

To create a top-level menu:

```php
'menu_parent' => 'top_level',
'icon_url'    => 'dashicons-admin-network',
'position'    => 65,
```

## Running a manual license check

```php
UUPD_License_UI::check_license_for( 'tdlab', 'example-plugin' );
```

This calls the license `check` endpoint, updates the stored option, and flushes updater cache by default.

## Cron checks

Each instance registers a vendor-scoped cron hook name:

```text
uupd_license_check_{vendor}__{slug}
```

Example:

```text
uupd_license_check_tdlab__example_plugin
```

The class includes the cron callback, but your plugin should schedule the event if you want recurring checks.

Example:

```php
register_activation_hook( __FILE__, function () {
    if ( ! wp_next_scheduled( 'uupd_license_check_tdlab__example_plugin' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, 'twicedaily', 'uupd_license_check_tdlab__example_plugin' );
    }
} );

register_deactivation_hook( __FILE__, function () {
    wp_clear_scheduled_hook( 'uupd_license_check_tdlab__example_plugin' );
} );
```

## Debugging

Enable logging for a specific plugin slug:

```php
add_filter( 'updater_enable_debug', function ( $enabled, $slug = '' ) {
    return $slug === 'example-plugin';
}, 10, 2 );
```

Listen for structured log events:

```php
add_action( 'uupd/log', function ( $message, $slug, $context ) {
    error_log( '[UUPD] ' . $slug . ': ' . $message );
}, 10, 3 );
```

## Styling hooks

The generated CSS can be filtered:

```php
add_filter( 'uupd/license_ui/page_css', function ( $css, $slug, $instance ) {
    return $css;
}, 10, 3 );

add_filter( 'uupd/license_ui/inline_box_css', function ( $css, $slug, $instance ) {
    return $css;
}, 10, 3 );
```

## Admin notice filters

Suppress or customise whether the inactive-license notice appears:

```php
add_filter( 'uupd/license_ui/show_notice/tdlab/example-plugin', function ( $show, $license, $instance ) {
    return $show;
}, 10, 3 );
```

Global form:

```php
add_filter( 'uupd/license_ui/show_notice', function ( $show, $slug, $license, $instance ) {
    return $show;
}, 10, 4 );
```

## Migration notes from older UUPD integrations

When moving from a slug-only UUPD V1-style setup:

1. Add a stable `vendor` to the updater config and license UI config.
2. Update filters from `uupd/<filter>/<slug>` to `uupd/<filter>/<vendor>/<slug>`.
3. Update cache prefixes from `upd_<slug>` assumptions to `uupd_<vendor>__<slug>`.
4. Clear WordPress transients and object cache after deployment.
5. Validate update checks in staging before production rollout.

## Security notes

- License form posts use WordPress nonces.
- Admin actions require the configured capability, defaulting to `manage_options`.
- License keys are masked in the UI and logs.
- wp-config.php-managed license keys are not stored in `wp_options`.
- Remote license calls use JSON over the configured license server URL; use HTTPS in production.

## Production checklist

- [ ] UUPD 2.0 updater bundled and loaded.
- [ ] License UI class bundled and loaded.
- [ ] Same `vendor` and `slug` used for updater and license UI.
- [ ] `item_id` matches the licensed product on the license server.
- [ ] `license_server` points to the UUPD Server site.
- [ ] `metadata_base` points to the update metadata source.
- [ ] License endpoints tested for activate, deactivate, and check.
- [ ] Transients cleared after migration.
- [ ] Update checks tested in staging.
- [ ] wp-config.php constant documented for managed installs.

## License

GPL 3.0
