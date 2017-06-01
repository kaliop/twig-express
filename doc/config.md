TwigExpress configuration reference
===================================

Define configuration in a `twigexpress.json` file at the root of your project (e.g., in the same folder as the `twigexpress.phar` file).

- [Configuration defaults](#configuration-defaults)
- [`allow_only`](#allow-only)
- [`debug_mode`](#debug-mode)
- [`global_vars`](#global-vars)
- [`namespaces`](#namespaces)
- [`twig_options`](#twig-options)


## Configuration defaults

TwigExpress will use the following configuration defaults:

```json
{
  "allow_only": ["*"],
  "debug_mode": true,
  "global_vars": {},
  "namespaces": {},
  "twig_options": {
    "autoescape": true,
    "cache": false,
    "charset": "utf-8",
    "debug": true,
    "strict_variables": true
  }
}
```

## `allow_only`

```json
{
  "allow_only": [
    "*.twig", "*.html", "*.xml", "*.json",
    "*.js", "*.css", "*.map", "*.woff*",
    "*.jpg", "*.png", "*.svg"
  ]
}
```

By default, TwigExpress will serve any file, with a few exceptions (files with `.php` or `.phar` extensions, and `.htaccess` files).

When using the `allow_only` option, TwigExpress will only serve or render files that match the provided patterns. Note that this applies to Twig templates as well, so you will need to include `"*.twig"`, as well as the patterns for any static file you wish to access (CSS, JS, fonts, etc.).

This option can be useful when sharing a prototype on a private test server.

## `debug_mode`

```json
{
  "debug_mode": true
}
```

The `debug_mode` is on by defaut. Setting it to false will disable the following features:

1. directory listings,
2. detailed errors,
3. Twig source view.

## `global_vars`

```json
{
  "global_vars": {
    "FACEBOOK_APP_ID": "542F159A0213",
    "someDefaultValue": false
  }
}
```

Defines global variables to inject in all templates.

Variable values can be strings, numbers, booleans, arrays or literal objects (associative arrays in PHP). You can nest objects as deep as you’d like.

Variables can then be accessed in any Twig template rendered by TwigExpress:

```twig
Our Facebook App ID is: {{ 542F159A0213 }}

{% if someDefaultValue %}
  The "someDefaultValue" variable is true.
{% else %}
  The "someDefaultValue" variable is false.
{% endif %}
```

## `namespaces`

```json
{
  "namespaces": {
    "my_namespace": "./relative/path/to/dir",
    "OtherNamespace": "/Users/me/Sites/something"
  }
}
```

Defines Twig template namespaces that can be used when extending or including templates.

This feature has little value when creating a prototype from scratch with TwigExpress, but can come in handy if you need to reuse existing templates that rely on namespaces.

The key is your Twig namespace name, and the value must be a path to an existing directory. This path must be an absolute path (e.g. `/var/www/my-website` or `C:/WWW/MyWebsite`), or it must start with `./` for a relative path starting from the site’s root dir.

Namespaces can then be used with all Twig tags and functions that reference files or templates:

```twig
{% extends '@my_namespace/layout.twig' %}

{% block content %}
  {{ source('@OtherNamespace/subdir/something.css') }}
{% endblock %}
```

## `twig_options`

```json
{
  "twig_options": {
    "autoescape": true,
    "cache": false,
    "charset": "utf-8",
    "debug": true,
    "strict_variables": true
  }
}
```

See the [Twig documentation on environment options][TWIG_OPTIONS] for possible values.

[TWIG_OPTIONS]: https://twig.sensiolabs.org/doc/1.x/api.html#environment-options
