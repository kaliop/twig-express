Advanced configuration
======================

Configuration goes in in `twigexpress.json` file at the root of your project (i.e., in the same folder as the `twigexpress.phar` file).

## Configuration Defaults

```json
{
  "autoescape": true,
  "strict_variables": true,
  "globals": {},
  "namespaces": {}
}
```

### `autoescape`

Whether Twig should escape HTML content when using `{{ myVar }}`?

```json
{
  "autoescape": true
}
```

For more information, see the section on `autoescape` [in the Twig documentation](http://twig.sensiolabs.org/doc/api.html#environment-options).

### `strict_variables`

Should we show an error when trying to use an undefined variable?

```json
{
  "strict_variables": true
}
```

See the section on `strict_variables` [in the Twig documentation](http://twig.sensiolabs.org/doc/api.html#environment-options).

### `globals`

Defines global variables to inject in all templates.

```json
{
  "globals": {
    "variableName": "Variable value"
  }
}
```

Variable values can be strings, numbers, booleans, arrays or literal objects (associative arrays in PHP). You can nest literal objects (associative arrays) as deep as you’d like.

### `namespaces`

Defines Twig template namespaces that can be used when extending or including templates. (This feature has little value when creating a prototype from scratch with TwigExpress, but can come in handy if you need to reuse existing templates that rely on namespaces.)

```json
{
  "namespaces": {
    "my_namespace": "./relative/path/to/dir",
    "OtherNamespace": "/Users/me/Sites/something"
  }
}
```

The key is your Twig namespace name, and the value must be a path to an existing directory. This path must be an absolute path (e.g. `/var/www/my-website` or `C:/WWW/MyWebsite`), or it must start with `./` for a relative path starting from the site’s root dir.

Namespaces can then be used with all Twig tags and functions that reference files or templates:

```twig
{% extends '@my_namespace/layout.twig' %}

{% block content %}
  {{ source('@OtherNamespace/subdir/something.css') }}
{% endblock %}
```
