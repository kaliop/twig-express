TwigExpress
===========

*[Download][DOWNLOAD]. Unzip. Write [Twig templates](#how-can-i-learn-twig) and see the result.*

TwigExpress packages the Twig templating engine, and a few other tools, in a single file. Our goal is to make it easy to get started with Twig if you’re a designer or a front-end developer, without having to install a big PHP framework or a CMS.

*Features*

- Renders Twig templates! (And shows useful information when things go wrong.)
- Lets you define global variables that all templates have access to.
- And a few extra tools: convert Markdown text to HTML, list files and folders…

*Documentation*

1. [# Installation](#installation)
2. [# Where should I add my content?](#where-should-i-add-my-content)
3. [# How can I learn Twig?](#how-can-i-learn-twig)
4. [# Defining global variables](#defining-global-variables)
5. [# Extra features](#extra-features)
6. [# Advanced configuration](#advanced-configuration)
7. [# License info](#license-info)


Installation
------------

Requirements:

- Any local PHP+Apache install (MAMP, WAMP, XAMPP etc.)
- Or PHP 5.4+ available on the command line

### Option 1: with Apache and PHP

This option can be useful if you have installed Apache and PHP through MAMP (Mac), WAMP or XAMPP (Windows) or something similar.

1. [Download a ZIP of the example project][DOWNLOAD] and unzip it.
2. Rename the unzipped folder to just `twigexpress` (or any other name you like).
3. Copy this folder in your web root (the `htdocs` directory with MAMP or XAMPP, or the `www` directory with WAMP).
4. Load `http://localhost/twigexpress/` in a web browser.

### Option 2: with PHP only

Let’s say you have PHP available on the command line. How can you tell? Open a Terminal or Command Prompt, type `php -v` and hit Enter; if you see something that looks like "PHP 5.x.y (cli)" and other bits of information, you should be alright.

1. [Download a ZIP of the example project][DOWNLOAD] and unzip it.
2. Put the unzipped folder wherever you want.
3. Open a Terminal or Command Prompt *in that folder*, and run:<br>
   `php --server localhost:8000 twigexpress.phar`.
4. Load `http://localhost:8000/` in a web browser.

### ⚠ Do not use TwigExpress in production

We recommend using TwigExpress to:

- discover Twig;
- build HTML prototypes with Twig, CSS and JS.

Please do not use this tool for live, user-facing websites. It is *not* full-featured, and may have security flaws.


Where should I add my content?
------------------------------

Anywhere! After installing, you should have a folder whose content looks like this:

```
twigexpress/
    demo/
    .htaccess
    index.twig
    README.md
    twigexpress.json
    twigexpress.phar
```

If you don’t care about the demo files, you can remove them, keeping only this:

```
twigexpress/
    .htaccess
    twigexpress.json
    twigexpress.phar
```

You can add your own content anywhere. By content we mean: Twig templates (which must have the `.twig` extension), CSS, scripts, images, etc. File names and URLs will match, but you should omit the `.twig` extension in URLs. We’re also using `index.twig` and `index.html` files as directory index, if they exist. For example:

- `[project folder]/index.twig` → can be accessed at `http://localhost:8000/`
- `[project folder]/some/page.twig` → can be accessed at `http://localhost:8000/some/page`
- `[project folder]/css/styles.css` → can be accessed at `http://localhost:8000/css/styles.css`

Note that you can organize your content any way you like. There is no enforced convention for where to place stylesheets, JavaScript, etc. Do whatever you want. :)


How can I learn Twig?
---------------------

See [our short introduction to Twig](doc/twig-intro.md).


Defining global variables
-------------------------

You can add global variables that will be available in every template rendered by TwigExpress.

Create a file called `twigexpress.json` in the same folder as `twigexpress.phar`. This file could look like this:

```json
{
    "globals": {
        "FACEBOOK_APP_ID": "542F159A0213",
        "someDefaultValue": false
    }
}
```

Then in every template you will be able to use the variables, e.g. like this:

```twig
Our Facebook App ID is: {{ 542F159A0213 }}

{% if someDefaultValue %}
    The "someDefaultValue" variable is true.
{% else %}
    The "someDefaultValue" variable is false.
{% endif %}
```

You can add as many global variables as you need. The values can be strings, numbers, arrays or objects, as long as they follow [the JSON format][JSON_EXAMPLE].


Extra features
--------------

We’re trying to avoid adding special variables, functions and filters that "normal" Twig does not have. Why? Because the templates you write using this simple Twig renderer should be easy to port to another Twig environment (e.g. Symfony, Drupal 8, WordPress with [Timber][]).

We still included a few special variables or functions, because they help with prototyping. If you decide to use those features, and are working with PHP developers on a project for a specific platform (Drupal 8, Symfony, etc.), be sure to warn them about this compatibility gotcha.

### URLS to assets

The `_base` variable represents the base URL of your TwigExpress install. It should be just `/` most of the time, or it will be `/foldername/` if you have installed TwigExpress in a subfolder with Apache and are accessing it at `http://localhost/foldername/`.

We recommend using the `_base` variable whenever you’re referencing assets or making URLs between pages:

```twig
<link rel="stylesheet" href="{{ _base }}assets/css/styles.css">
<img src="{{ _base }}images/home_banner.jpg" alt="">
<a href="{{ _base }}pages/home">Home template</a>
```

This makes your code more portable if you want to share it with others (for example if they install it in a subfolder and you don’t).

### GET and POST values

You can use these variables to access GET and POST parameters, as well as cookie data:

- `_get`: an array of GET parameters.
- `_post`: an array of POST parameters.
- `_cookie`: an array of current cookies.

### Fake latin text

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

{# Wrap the paragraphs in <p> tags #}
{{ lorem('1-5p', 'p') | raw }}

{# Wrap the command in brackets, get an array of strings. #}
<ul>
{% for item in lorem('[7 sentences]') %}
  <li>{{ item }}</li>
{% endfor %}
</ul>
```

### Markdown to HTML

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

### Listing files and folders

The `files` and `folders` functions let you list the content of a given directory, using PHP’s `glob` function.

```twig
{# List of all templates in the 'pages' directory #}
{% for filename in files('*.twig', 'pages') %}
    {# The '~' operator concatenates strings, and the 'replace' filter lets us
       remove the '.twig' extension because we don’t want it in the URL. #}
    {% set url = _base ~ 'pages/' ~ filename|replace({'.twig':''}) %}
    <a href="{{ url }}">{{ filename }}</a><br>
{% endfor %}

{# List first and second-level folders #}
{% for foldername in folders(['*', '*/*']) %}
    {{ foldername }}<br>
{% endfor %}
```

The two functions work exactly the same, but `files` matches files and `folders` matches… you know. Both functions can take up to two parameters:

- `patterns`: one glob pattern (string) or several (array of strings) to look for
- `context`: path of the folder where we should start looking (by default, the project root)


Advanced configuration
----------------------

You can change or add some configuration in the `twigexpress.json` file.

- `autoescape`: should Twig escape content when using `{{ myVar }}`? (Defaults to true.)
- `strict_variables`: should we show an error when trying to use an unknown variable? (Defaults to true.)
- `namespaces`: define Twig namespace.

### Defining a Twig namespace

Define Twig namespaces like this in `twigexpress.json`:

```json
{
    "namespaces": {
        "my_namespace": "./relative/path/to/dir",
        "OtherNamespace": "/Users/me/Sites/something"
    }
}
```

The key is your Twig namespace name, and the value must be a path to an existing directory. This path must be an absolute path (e.g. `/var/www/my-website` or `C:/WWW/MyWebsite`), or it must start with `./` for a relative path starting from the site’s root dir.

### Using namespaces in templates

Namespaces can be used with `{% include %}` or the `source()` function in templates:

```twig
{% extends '@my_namespace/layout.twig' %}

{% block content %}
  {{ source('@OtherNamespace/subdir/something.css') }}
{% endblock %}
```

License info
------------

- [TwigExpress][] (this script, main source in `twigexpress/src`): MIT License, see `LICENSE`
- [Twig library][TWIG_LIB] (`twigexpress/lib/Twig`): BSD License, see `twigexpress/lib/Twig/LICENSE`
- [Karwana\Mime][MIME_LIB] (`twigexpress/lib/Mime`): MIT License, see `twigexpress/lib/Mime/LICENSE`
- [Parsedown][] (`twigexpress/lib/Parsedown`): MIT License, see `twigexpress/lib/Parsedown/LICENSE.txt`
- [php-loremipsum][] (`twigexpress/lib/LoremIpsum`): MIT License, see `twigexpress/lib/LoremIpsum/LICENSE`
- [highlight.js][]: (`twigexpress/tpl/highlight.pack.js`) BSD License


[TwigExpress]: https://github.com/gradientz/twig-express
[DOWNLOAD]: https://github.com/gradientz/twig-express/archive/download.zip
[JSON_EXAMPLE]: https://en.wikipedia.org/wiki/JSON#Example
[TWIG_HOME]: http://twig.sensiolabs.org/
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[Parsedown]: http://parsedown.org/
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[highlight.js]: https://github.com/isagalaev/highlight.js
[Timber]: http://upstatement.com/timber/
