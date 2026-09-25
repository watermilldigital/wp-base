<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".github/logo-dark.svg">
    <img src=".github/logo-light.svg" alt="WaterMill" width="220" height="30">
  </picture>
</p>

<p align="center">
  <a href="https://github.com/watermilldigital/wp-base/tags"><img src="https://img.shields.io/badge/version-v3.3.1-blue" alt="Version"></a>
  <img src="https://img.shields.io/badge/php-%5E8.4-777bb4" alt="PHP ^8.4">
  <img src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue" alt="License: GPL-2.0-or-later">
</p>

# WP Base

The must-use plugin every WaterMill WordPress site starts from: security hardening, safety on non-production environments, and removal of WordPress defaults we never use.

It's a must-use plugin so it loads whatever theme is active, and nobody can deactivate it from wp-admin.

## What it does

Each feature is one file in `src/`:

| File | What it does |
| --- | --- |
| `hardening.php` | Removes the WordPress version generator tag (page head and feeds), empties the XML-RPC method table, turns off application passwords, and blocks username enumeration (REST `/wp/v2/users` for logged-out requests, `?author=N`, and the users sitemap). |
| `environment.php` | Anywhere `WP_ENVIRONMENT_TYPE` isn't `production`: sets noindex on every page and blocks all outgoing mail, logging each blocked email to `debug.log`. |
| `disable-comments.php` | Turns off comments and pingbacks everywhere, and removes them from wp-admin and the dashboard. |
| `disable-emoji.php` | Removes WordPress's emoji detection script and styles. |
| `varnish-purge.php` | Cloudways only (no-op elsewhere): purges the whole Varnish cache when published content, terms, menus or the Customizer change, and adds a "Purge cache" admin bar button. |
| `login-limit.php` | Locks an IP out of wp-login.php for 15 minutes after 5 failed logins. Reads the real IP from Cloudflare's `CF-Connecting-IP` header, but only when the request came from a Cloudflare address. |
| `svg-uploads.php` | Allows SVG uploads for users with `unfiltered_html` (Administrators; Super Admins on multisite), who can already publish raw scripts. Everyone else keeps the default file types, so there's nothing to sanitise. |
| `branding.php` | Replaces WordPress branding with WaterMill's: the login screen logo (linking to watermilldigital.com), "— WordPress" in login and wp-admin tab titles, the admin bar W menu and "Howdy,", the admin footer ("Built by WaterMill Digital"), the dashboard Welcome panel and WordPress Events and News (replaced by a WaterMill Digital contact widget, first on the dashboard until a user moves it), and "WordPress" as the sender name on system emails (the site name instead). |

Image handling (WebP conversion, compression, savings tracking) lives in [WP Image Compression](https://github.com/watermilldigital/wp-image-compression), not here.

## Install

The repo is private, so add it as a VCS repository and require it:

```sh
composer config repositories.wp-base vcs https://github.com/watermilldigital/wp-base
composer require watermilldigital/wp-base:^3.0
```

The project needs [`composer/installers`](https://github.com/composer/installers) with a `wordpress-muplugin` path, for example:

```json
"extra": {
    "installer-paths": {
        "public/wp-content/mu-plugins/{$name}/": ["type:wordpress-muplugin"]
    }
}
```

WordPress only loads PHP files at the top level of `mu-plugins/`, not packages in subdirectories, so the project also needs a flat loader file such as `mu-plugins/autoloader.php`:

```php
<?php
/**
 * Plugin Name: MU Autoloader
 * Description: Loads Composer-installed mu-plugin packages. Entry file is named after its directory.
 */

foreach ( glob( WPMU_PLUGIN_DIR . '/*', GLOB_ONLYDIR ) as $dir ) {
	$entry = $dir . '/' . basename( $dir ) . '.php';

	if ( file_exists( $entry ) ) {
		require_once $entry;
	}
}
```

### CI

GitHub Actions needs a token to install a private package. Create a fine-grained PAT with read-only **Contents** access to this repo, save it as a repository secret, and set it on the `composer install` step:

```yaml
env:
  COMPOSER_AUTH: '{"github-oauth":{"github.com":"${{ secrets.COMPOSER_GITHUB_TOKEN }}"}}'
```

## Skipping a feature

Every file in `src/` loads by default. To skip any of them on a project, define `WP_BASE_SKIP` in `wp-config.php`, listing file names without `.php`:

```php
define( 'WP_BASE_SKIP', array( 'disable-comments' ) );
```

For example, WooCommerce product reviews are comments, so a shop needs `disable-comments` skipped.

## Development

```sh
composer install
composer check   # phpstan + phpcs (WordPress coding standards)
```

To add a feature, drop a new file in `src/`. It loads automatically.

Release by bumping the version badge at the top of this README and the `Version:` header in `wp-base.php`, then tagging (`git tag v1.1.0 && git push origin v1.1.0`). The badge is static because shields.io can't read tags from a private repo. After tagging, run `composer update watermilldigital/wp-base` in each project.

## License

Copyright © WaterMill Digital. Licensed under [GPL-2.0-or-later](LICENSE), the same licence as WordPress.
