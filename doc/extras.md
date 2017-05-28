TwigExpress-specific features
=============================

To help with basic HTML prototyping, we included a few special variables and functions. Be careful when using these features if your code will be ported later to another Twig environment (Symfony, Laravel, Drupal 8, WordPress with [Timber](http://upstatement.com/timber/), etc.) which doesn’t have these extra features!

- [`param` - HTTP values](#param-http-values)
- [`lorem` - Dummy text](#lorem-dummy-text)
- [`markdown` - Markdown to HTML](#markdown-markdown-to-html)
- [Listing `files` and `folders`](#listing-files-and-folders)
- [Using the TwigExpress page style](#using-the-twigexpress-page-style)


`param` - HTTP values
---------------------

You can retrieve GET, POST and cookie values using the `param()` function.

This can be useful for prototyping, for instance if you want to be able to show a page with the navigation bar shown or hidden, you could add `?shownav=1` in its URL, and use the following Twig code:

```twig
<nav{% if not param('shownav') %} hidden{%endif%}>
```

By default the `param()` function will look in GET parameters (PHP’s `$_GET` array), and if it can’t find the requested value it will return an empty string. You can provide a different fallback value as a second parameter:

```twig
{{ param('something', 'My default value') }}
```

To get a POST value or a cookie value instead, use a `post:` or `cookie:` prefix in the parameter’s name:

```twig
{# The 'get:' prefix is implied #}
{{ param('something') }}
{{ param('get:something') }}

{# The prefix is case-insensitive, but not the actual key #}
{{ param('post:something') }}
{{ param('POST:something') }}
```


`lorem` - Dummy text
--------------------

You can generate fake latin text with the `lorem()` function.

```twig
{# Ten words of fake latin? Sure. #}
{{ lorem('10 words') }}

{# You can also ask for sentences or paragraphs,
   and abbreviate the command to a single letter. #}
{{ lorem('5 sentences') }}
{{ lorem('5 s') }}
{{ lorem('3 paragraphs') }}
{{ lorem('3p') }}

{# Want a random number of elements? #}
{{ lorem('1-5w') }}
{{ lorem('5-10 sentences') }}

{# Wrap the command in brackets to get an array of strings. #}
<ul>
{% for item in lorem('[7 sentences]') %}
  <li>{{ item }}</li>
{% endfor %}
</ul>
```


`markdown` - Markdown to HTML
-----------------------------

The `markdown()` function converts a string to HTML (using [Parsedown][]).

```twig
{% set mdSource %}
# Cool Markdown example
Want to [learn Markdown in 60 seconds](http://commonmark.org/help/)?
{% endset %}

Markdown example:
{{ markdown(mdSource) }}

Don't want paragraph tags?
{{ markdown('Short and *sweet* text', inline=true) }}
```

Listing `files` and `folders`
-----------------------------

The `files` and `folders` functions let you list the content of a given directory, using PHP’s `glob` function.

```twig
{# List of all templates in the 'pages' directory #}
{% for path in files('pages/*.twig') %}
  {# The '~' operator concatenates strings, and the 'replace' filter lets us
     remove the '.twig' extension because we don’t want it in the URL. #}
  {% set url = path|replace({'.twig':''}) %}
  {% set filename = path|split('/')|last %}
  <a href="{{ url }}">{{ filename }}</a><br>
{% endfor %}

{# List first and second-level folders #}
{% for path in folders(['*', '*/*']) %}
  {{ path }}<br>
{% endfor %}
```

The two functions work exactly the same, but `files` matches files and `folders` matches… you know. Both functions accept either a string or an array of strings (glob patterns).


Using the TwigExpress page style
--------------------------------

You can make use of the same HTML page layout and styling as the TwigExpress pages, by extending a template returned by the `twigexpress_layout` function. This might look like:

```twig
{% extends twigexpress_layout() %}

{% block content %}
  <h1>My Awesome Page</h1>
  <p>Hello there.</p>
{% endblock %}

{% block styles %}
  {{ parent() }}
  <style>/* Adding custom styles */</style>
{% endblock %}
```
