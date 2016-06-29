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
4. [# Configuration and variables](#configuration-and-variables)
5. [# Additional features](#additional-features)
6. [# License info](#license-info)


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
   `php -S localhost:8000 twigexpress.phar`.
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

Let’s start here! In addition to this introduction, you should probably read [Twig for Template Designers][TWIG_INTRO] as well (you could open it in a new tab and come back to it later), and keep the [Twig documentation][TWIG_DOC] around to check what the available Twig tags, filters and functions are.

### Using variables

Creating and using variables is simple:

```twig
{# Hey I’m a comment! On the next few lines, you can see
   a Twig tag and the syntax for outputting content. #}
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
{# Define default values when using variables #}
<h1>{{ pageTitle|default('No title') }}</h1>
{% for i in 1..numItems|default(10) %}
  {{ i }}<br>
{% endfor %}

{# Or define default values beforehand, at the start of your template maybe #}
{% set pageTitle = pageTitle|default('No title') %}
{% set numItems = numItems|default(10) %}
...
<h1>{{ pageTitle }}</h1>
{% for i in 1..numItems %}
  {{ i }}<br>
{% endfor %}
```

### Including stuff

Many people have dabbled in PHP before, writing `<?php … ?>` tags just for one thing: the `include()` function. That’s also a perfectly good reason for using Twig. :)

With TwigExpress, all your includes start from the root folder. So if you have this structure:

```
twigexpress.phar
blocks/
    header.twig
section1/
    page1.twig
    page2.twig
```

When using the `{% include %}` tag, you will have to give the complete path from the root folder:

```twig
{# Yes! #}
{% include 'blocks/header.twig' %}
{% include 'section1/page1.twig' %}

{# Nope. Relative paths won't work, sorry! #}
{% include 'page2.twig' %}
{% include '../blocks/header.twig' %}
```

With Twig, you can include a template and pass it some variables:

```twig
{% include 'blocks/articleteaser.twig' with {
    title: 'Awesome title',
    image: 'assets/img/teaser/1.jpg',
    text: 'Lorem ipsum dolor sit amet, consectetur adipisicing elit…'
} %}
```

Note that these examples we used the `include` tag. There’s also a similar `include` function if you like that syntax better:

```twig
{{ include('blocks/header.twig') }}
{{ include('blocks/articleteaser.twig', {title: 'Awesome title'}) }}
```

You can also include the content of a file without rendering it as a Twig template. This can be quite useful for content that is *not* a Twig template (especially CSS, JS or JSON content). For that, use the `source` function.

```twig
<script>
    {{ source('assets/js/analytics.js') }}
</script>
```

### Extending a parent template

Next up is the topic of blocks and extends. They’re a really powerful tool, especially for building HTML pages. Rather than describing it here, we’ll refer you to [Twig’s introduction to template inheritance][TWIG_INHERITANCE].

That’s it, you know everything you need to get started with Twig!


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


Additional features
-------------------

We’re trying to avoid adding special variables, functions and filters that normal Twig does not have. Why? Because the templates you write using this simple Twig renderer should be easy to port to another Twig environment (e.g. Symfony, Drupal 8, WordPress with [Timber][]).

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


License info
------------

- [TwigExpress][] (this script, main source in `twigexpress/src`): MIT License, see `LICENSE`
- [Twig library][TWIG_LIB] (`twigexpress/lib/Twig`): BSD License, see `twigexpress/lib/Twig/LICENSE`
- [Karwana\Mime][MIME_LIB] (`twigexpress/lib/Mime`): MIT License, see `twigexpress/lib/Mime/LICENSE`
- [Parsedown][] (`twigexpress/lib/Parsedown`): MIT License, see `twigexpress/lib/Parsedown/LICENSE.txt`
- [php-loremipsum][] (`twigexpress/lib/LoremIpsum`): MIT License, see `twigexpress/lib/LoremIpsum/LICENSE`


[TwigExpress]: https://github.com/gradientz/twig-express
[DOWNLOAD]: https://github.com/gradientz/twig-express/archive/example.zip
[JSON_EXAMPLE]: https://en.wikipedia.org/wiki/JSON#Example
[TWIG_HOME]: http://twig.sensiolabs.org/
[TWIG_DOC]: http://twig.sensiolabs.org/documentation
[TWIG_INTRO]: http://twig.sensiolabs.org/doc/templates.html
[TWIG_INHERITANCE]: http://twig.sensiolabs.org/doc/templates.html#template-inheritance
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[Parsedown]: http://parsedown.org/
[Timber]: http://upstatement.com/timber/
