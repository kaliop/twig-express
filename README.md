TwigExpress
===========

*[Download][DOWNLOAD]. Unzip. Write [Twig templates][TWIG_HOME] and see the result.*

TwigExpress is a the Twig templating engine, plus a few tools, packaged in a single file. Our goal is to make Twig easy to get started with if you’re a designer or a front-end developer, without having to use a big PHP framework or a CMS.

*Features*

- Renders Twig templates! (And shows useful error templates if things go wrong.)
- Lets you inject global variables that all templates have access to.
- And a few extra tools: convert Markdown to HTML, list files and folders…

*Documentation*

- [# Installation](#installation)
- [# Adding your content](#adding-your-content)
- [# Configuration and variables](#configuration-and-variables)
- [# Using Twig](#using-twig)
- [# Non-standard features](#non-standard-features)
- [# Licenses](#licenses)


Installation
------------

Requirements:

- PHP 5.4+ available on the command line
- Or any local PHP+Apache install (MAMP, WAMP, XAMPP etc.)

### Option 1: with PHP only

Let’s say you have PHP available on the command line. How can you tell? Open a Terminal or Command Prompt, type `php -v` and hit Enter; if you see something that looks like "PHP 5.x.y (cli)" and other bits of information, you should be alright.

1. [Download a ZIP of the example project][DOWNLOAD] and unzip it.
2. Put the unzipped folder wherever you want.
3. Open a Terminal or Command Prompt *in that folder*, and run:<br>
   `php -S localhost:8000 twigexpress.phar`.
4. Load `http://localhost:8000/` in a browser.

### Option 2: with Apache and PHP

This option can be useful if you have installed Apache and PHP through MAMP (Mac), WAMP or XAMPP (Windows) or something similar.

1. [Download a ZIP of the example project][DOWNLOAD] and unzip it.
2. Rename the unzipped folder to just `twigexpress` (or any other name you like).
3. Copy this folder in your web root (the `htdocs` directory with MAMP or XAMPP, or the `www` directory with WAMP).
4. Load `http://localhost/twigexpress/` in a browser.

### ⚠ Do not use TwigExpress in production

We recommend using TwigExpress to:

- discover Twig;
- build front-end prototypes with Twig, CSS and JS (you can organize your files any way you like).

Please do not use this tool for live, user-facing websites. It is *not* full-featured, and may have security flaws.


Adding your content
-------------------

So you have a folder whose content looks like this:

```
twigexpress/
    example/
    .htaccess
    index.twig
    README.md
    twigexpress.json
    twigexpress.phar
```

If you don’t care about the example files, you can remove them, keeping only this:

```
twigexpress/
    .htaccess
    twigexpress.json
    twigexpress.phar
```

Now you can add your own content: Twig templates (file names should end in `.twig`), CSS, scripts, images, etc. File names and URLs will match, but you can omit the `.twig` extension in URLs. We’re also using `index.twig` and `index.html` files as directory index, if they exist. For example:

- `twigexpress/index.twig` → can be accessed at `http://localhost:8000/`
- `twigexpress/example/page.twig` → can be accessed at `http://localhost:8000/example/page`
- `twigexpress/css/styles.css` → can be accessed at `http://localhost:8000/css/styles.css`


Configuration and variables
---------------------------

You can change or add some configuration in a `twigexpress.json` file.

- `autoescape`: should Twig escape content when using `{{ myVar }}`?
- `strict_variables`: should we show an error when trying to use an unknown variable?
- `globals`: each property in this object will be available as a global variable in all templates.

What are global variables? For example if you have this `twigexpress.json` config:

```json
{
    "globals": {
        "FACEBOOK_APP_ID": "542F159A0213"
    }
}
```

… then in every template you will be able to use `{{ FACEBOOK_APP_ID }}` to output the corresponding value.

You can add as many global variables as you need. The values can be strings, numbers, arrays or objects, as long as they follow the JSON format ([example here][JSON_EXAMPLE]).


Using Twig
----------

Just discovering Twig?

- Read [Twig for Template Designers][TWIG_INTRO].
- See the [Twig documentation][TWIG_DOC] for available Twig tags, filters and functions.

Here are a few pointers to get you started.

### Using variables

Creating and using variables is simple:

```twig
{% set pageTitle = 'Cool title!' %}
<h1>{{ pageTitle }}</h1>
```

Sometimes a variable may contain some HTML code, or HTML entitites, and by default Twig will escape this. If you want to use unescaped content, use the `raw` filter:

```twig
{% set pageTitle = '<em>Awesome</em> title!' %}
<h1>{{ pageTitle|raw }}</h1>
```

Be careful *not* to do this for content that you don’t controll (e.g. any user-submitted content, content from GET and POST parameters, etc.).

Finally, sometimes a variable may or may not be defined. To avoid getting an error, you can use the `default` filter:

```twig
{% set pageTitle = pageTitle|default('') %}
{% set numItems = numItems|default(10) %}
```

### Including stuff

if you want do some includes: all includes, extends (to extend a parent layout) etc. start from the root folder. So if you have this structure:

```
blocks/
    header.twig
section1/
    page1.twig
    page2.twig
```

You will have to give the complete path from the root folder to include a template:

```twig
{# Yes #}
{% include 'blocks/header.twig' %}
{% include 'section1/page1.twig' %}

{# Nope. Relative paths won't work, sorry! #}
{% include 'page2.twig' %}
{% include '../blocks/header.twig' %}
```

You can include a template and pass it some variables:

```twig
{% include 'blocks/articleteaser.twig' with {
    title: 'Awesome title',
    image: 'assets/img/teaser/1.jpg',
    text: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit…'
} %}
```

And if you want to include the contents of a file, without rendering it as a template, use the `source` function:

```twig
<script>{{ source('assets/js/analytics.js') }}</script>
```


Non-standard features
---------------------

We’re trying to avoid adding special variables, functions and filters that normal Twig does not have. Why? Because the templates you write using this simple Twig renderer should be easy to port to another Twig environment (e.g. Symfony, Drupal 8, WordPress with [Timber][]).

We still have a few special variables or functions available. If you use them are working with PHP developers on a project, make sure to warn them about this compatibility gotcha.

### URLS to assets

The `_base` variable represents the base URL of your TwigExpress install. It should be just `/` most of the time, or it could be `/foldername/` if you have installed TwigExpress in a subfolder with Apache and are accessing it at `http://localhost/foldername/`.

We recommend using the `_base` variable whenever you’re referencing assets:

```twig
<link rel="stylesheet" href="{{ _base }}assets/css/styles.css">
<img src="{{ _base }}images/home_banner.jpg" alt="">
```

This makes your code more portable if you want to share it with others.

### GET and POST values

You can use these variables to access GET and POST parameters, as well as cookie data:

- `_get`: an array of GET parameters.
- `_post`: an array of POST parameters.
- `_cookie`: an array of current cookies.

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

### Fake latin text

You can generate fake latin text with the `lorem()` function.

```twig
Ten words of fake latin? Sure.
{{ lorem('10 words') }}

You can also ask for sentences or paragraphs, and abbreviate the command to a single letter:
{{ lorem('5 sentences') }}
{{ lorem('4s') }}
{{ lorem('3 paragraphs') }}
{{ lorem('2p') }}

If you wrap the command in brackets, you get an array that you can loop over:
<ul>
{% for item in lorem('[7 sentences]') %}
  <li>{{ item }}</li>
{% endfor %}
</ul>
```


Licenses
--------

- TwigExpress script: MIT License, see `LICENSE`
- [Twig library][TWIG_LIB] (`main/lib/Twig`): BSD License, see `main/lib/Twig/LICENSE`
- [Karwana\Mime][MIME_LIB] (`main/lib/Mime`): MIT License, see `main/lib/Mime/LICENSE`
- [Parsedown][] (`main/lib/Parsedown`): MIT License, see `main/lib/Parsedown/LICENSE.txt`
- [php-loremipsum][] (`main/lib/LoremIpsum`): MIT License, see `main/lib/LoremIpsum/LICENSE`


[DOWNLOAD]: https://github.com/gradientz/twig-express/archive/example.zip
[JSON_EXAMPLE]: https://en.wikipedia.org/wiki/JSON#Example
[TWIG_HOME]: http://twig.sensiolabs.org/
[TWIG_DOC]: http://twig.sensiolabs.org/documentation
[TWIG_INTRO]: http://twig.sensiolabs.org/doc/templates.html
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[Parsedown]: http://parsedown.org/
[Timber]: http://upstatement.com/timber/
