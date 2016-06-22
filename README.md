TwigExpress
===========

*[Download][DL]. Unzip. Write [Twig templates][TWIG_HOME] and see the result.*


What is this thing?
-------------------

TwigExpress is a simple PHP script for getting started with the Twig templating engine. Our goal is to make this script as easy to use as possible (e.g. reducing the number or complexity of installation steps).

What it does:

1. Serve static files directly.
2. If the requested file is a Twig template, render it.
3. Show a useful error page if there is an error.
 
⚠ ️**Important: do not use TwigExpress in production, ever.**

You can use TwigExpress to build front-end prototypes with Twig, CSS and JS. There is no obligation to use a specific folder structure for your assets, pages, templates etc. Do whatever works for you.


Requirements
------------

- PHP 5.4+ available on the command line (open a Terminal or Command Prompt, type `php -v` and hit Enter; if you see something that looks like "PHP 5.x.y (cli)" and other bits of information, you should be alright).
- Or any local PHP+Apache install (MAMP, WAMP, XAMPP etc.).


Installation
------------

[Download a ZIP of the example project][DL] and unzip it. Bam, you’re done.<br>
Okay, now how do you see the HTML pages?

### With PHP only

1. Put the unzipped folder wherever you want.
2. Open a Terminal or Command Prompt *in that folder*, and run:<br>
   `php -S localhost:8000 twigexpress.phar`.
3. Load `http://localhost:8000/` in a browser.

### With Apache Apache (MAMP/WAMP/etc)

1. Rename the unzipped folder to just `twigexpress` (or any other name you like).
2. Copy this folder in your web root (the `htdocs` directory with MAMP or XAMPP, or the `www` directory with WAMP).
3. Load `http://localhost/twigexpress/`.


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

Now you can add your own content: Twig templates (file names should end in `.twig`), CSS, scripts, images, etc. File names and URLs will match, but you can omit the `.twig` extension in URLs. We’re also using `index.twig` and `index.html` files as directory index, if they exist. For instance:

-   `twigexpress/index.twig` → can be accessed at `http://localhost:8000/`
-   `twigexpress/example/page.twig` → can be accessed at `http://localhost:8000/example/page`
-   `twigexpress/css/styles.css` → can be accessed at `http://localhost:8000/css/styles.css`


Configuration
-------------

You can change or add some configuration in a `twigexpress.json` file.

- `autoescape`: should Twig escape content when using `{{ myVar }}`?
- `strict_variables`: should we show an error when trying to use an unknown variable?
- `globals`: each property in this object will be available as a variable in all templates.


Special variables
-----------------

We’re trying to avoid adding special variables, functions and filters to the Twig environment in this tool. The templates you write using this simple Twig renderer should be easy to port to another Twig environment (e.g. Symfony, Drupal 8, WordPress with [Timber][]).

We still have a few special variables or functions available:

- `_base`: the base URL of your install. Should be `/` most of the time, or `/subfoldername/` if you have installed TwigExpress in a subfolder with Apache and are accessing it at `http://localhost/subfoldername/`.
- `_get`: an array of GET parameters.
- `_post`: an array of POST parameters.
- `_cookie`: an array of current cookies.


Using Twig: a few pointers
--------------------------

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

Be careful NOT to do this for content that you don’t controll (e.g. any user-submitted content, content from GET and POST parameters, etc.).

Finally, sometimes a variable may or may not be defined. To avoid getting an error, you can use the `default` filter:

```twig
{% set pageTitle = pageTitle|default('') %}
{% set numItems = numItems|default(10) %}
```

### URLS to assets

We recommend using the `_base` variable when referencing assets.

```twig
<link rel="stylesheet" href="{{ _base }}assets/css/styles.css">
<img src="{{ _base }}images/home_banner.jpg" alt="">
```

This makes your code more portable if you want to share it with others (NOT as a live website).

### Including stuff

if you want do some includes: all includes, extends (to extend a parent layout) etc. start from the root folder. So if you have this structure:

```
_twig/
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


Licenses
--------

- TwigExpress script (`_twig/src`): MIT License
- Twig library (`_twig/lib/Twig`): BSD License, see http://twig.sensiolabs.org/license or `_twig/lib/Twig/LICENSE`
- Karwana\Mime class (`_twig/lib/Mime`): MIT License, see `_twig/lib/Mime/LICENSE`


[DL]: https://github.com/gradientz/twig-express/archive/example.zip
[TWIG_HOME]: http://twig.sensiolabs.org/
[TWIG_DOC]: http://twig.sensiolabs.org/documentation
[TWIG_INTRO]: http://twig.sensiolabs.org/doc/templates.html
[Timber]: http://upstatement.com/timber/
