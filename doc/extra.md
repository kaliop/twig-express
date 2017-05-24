TwigExpress-specific features
=============================

To help with basic HTML prototyping, we included a few special variables and functions. Be careful when using these features if your code will be ported later to another Twig environment (Symfony, Laravel, Drupal 8, WordPress with [Timber](http://upstatement.com/timber/), etc.) which doesn’t have these extra features!

- [# URLs to assets](#urls-to-assets)
- [# GET and POST values](#get-and-post-values)
- [# `lorem` - Dummy text](#lorem-dummy-text)
- [# `markdown` - Markdown to HTML](#markdown-markdown-to-html)
- [# Listing `files` and `folders`](#listing-files-and-folders)


URLs to assets
--------------

The `_base` variable represents the base URL of your TwigExpress install. It should be just `/` most of the time, or it will be `/foldername/` if you have installed TwigExpress in a subfolder with Apache and are accessing it at `http://localhost/foldername/`.

We recommend using the `_base` variable whenever you’re referencing assets or making URLs between pages:

```twig
<link rel="stylesheet" href="{{ _base }}assets/css/styles.css">
<img src="{{ _base }}images/home_banner.jpg" alt="">
<a href="{{ _base }}pages/home">Home template</a>
```

This makes your code more portable if you want to share it with others (for example if they install it in a subfolder and you don’t).


GET and POST values
-------------------

You can use these variables to access GET and POST parameters, as well as cookie data:

- `_get`: an array of GET parameters.
- `_post`: an array of POST parameters.
- `_cookie`: an array of current cookies.


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
