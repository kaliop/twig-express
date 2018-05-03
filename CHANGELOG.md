# Changelog

## 2.1.3

- Fix main download by removing test-specific config in `twigexpress.json`.
- Update Twig lib to 1.35.3.

## 2.1.2

- Remove separate 'download' branch, in favour of 'master'.
- Remove `.htaccess`, see `doc/apache.md`.
- Update highlight.js to 9.12.0.
- `php twigexpress.phar` prints a short message and the release version.

## 2.1.1

- Update Twig lib to 1.35.0.

## 2.1.0

- Rewrite the TwigExpress layout; partial style rewrite.

## 2.0.0

BREAKING CHANGES:

- Dropped support Apache when serving in a subfolder (e.g. `http://localhost/my-twigexpress-prototype/`); use VirtualHosts, or run TwigExpress from the command line.
- Removed the `_base` global variable; if you need it, redeclare it in the `global_vars` config in `twigexpress.json`.
- Removed the `_get`, `_post` and `_cookie` global variables; use the `param()` function instead.
- The `autoescape` and `strict_variables` config keys have been moved inside a `twig_options` object; see `doc/config.md` for details.
- Removed the second parameter for the `files()` and `folders()` functions.

New features:

- Automatic Markdown previews for files ending in `.md`.
- Config: `allow_only` - whitelist of file patterns to serve.
- Config: `debug_mode` - disable folder listings, source views, debug pages.
- Twig: `param()` function for getting GET, POST and cookie values.
- Twig: create pages using the TwigExpress design (with automatic breadcrumbs), with `{% extends twigexpress_layout() %}`.

## 1.1.0

- New feature: directory listings.
- New feature: define Twig namespaces in `twigexpress.json`.

## 1.0.0

- First public release.
