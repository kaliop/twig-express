TwigExpress
===========


*[Download][DOWNLOAD]. Unzip. Write [Twig templates](doc/twigintro.md) see the result.*

TwigExpress packages the [Twig templating engine][TWIG_HOME], and a few other tools, in a single file. Our goal is to make it easy to get started with Twig if you’re a designer or a front-end developer, without having to install a big PHP framework or a CMS.

*Table of contents*

1. [Summary of features](#summary-of-features)
2. [Installation](#installation)
3. [Adding content](#adding-content)
4. [Writing Twig templates](#writing-twig-templates)
5. [Configuration](#configuration)
6. [Library and license info](#library-and-license-info)


Summary of features
-------------------

-   Browse and serve files
-   Render Twig templates
-   Error pages with code excerpt
-   Source view with syntax highlighting
-   And a few extra tools for prototyping:
    -   generate faux text (“lorem ipsum”)
    -   convert Markdown text to HTML
    -   list files and folders…


Installation
------------

### Requirements

-   PHP 5.4+ available on the command line.<br>
    On macOS, you should have PHP installed already.<br>
    On Windows, one easy way to install PHP (and other tools) is [XAMPP](https://www.apachefriends.org/download.html).

### Installation and usage

1.  [Download a ZIP of the example project][DOWNLOAD] and unzip it.
2.  Open a Terminal or Command Prompt *in that folder*, and run:<br>
    `php --server localhost:8000 twigexpress.phar`.
3.  Load `http://localhost:8000/` in a web browser to browse files.<br>
    Any file ending in `.twig` will be interpreted as a Twig template.

### ⚠ Do not use TwigExpress in production

We recommend using TwigExpress for:

1.  discovering (or play with) Twig;
2.  building HTML prototypes with Twig, CSS and JS.


Adding content
--------------

After installing TwigExpress, you should have a folder whose content looks like this:

```
myproject/
    demo/
    .htaccess
    index.twig
    README.md
    twigexpress.json
    twigexpress.phar
```

If you don’t care about the demo files, you can remove them, keeping only this:

```
myproject/
    .htaccess
    twigexpress.json
    twigexpress.phar
```

You can add your own content anywhere. By content we mean: Twig templates (which must have the `.twig` extension), CSS, scripts, images, etc. File names and URLs will match, but you should omit the `.twig` extension in URLs. We’re also using `index.twig` and `index.html` files as directory index, if they exist. For example:

- `myproject/index.twig` → can be accessed at `http://localhost:8000/`
- `myproject/some/page.twig` → `http://localhost:8000/some/page`
- `myproject/css/styles.css` → `http://localhost:8000/css/styles.css`

There is no enforced convention for where to place stylesheets, JavaScript, etc. Feel free to organize your static assets and templates however you want.


Writing Twig templates
----------------------

There are two sets of features available in templates:

-   **Syntax and features native to the Twig language**<br>
    See our short guide [“Getting started with Twig”](doc/twigintro.md), and the official [“Twig for Template Designers”][TWIG_INTRO].

-   **Features specific to TwigExpress**<br>
    We’re added [a few extra features](doc/extras.md) to help you write HTML prototypes, such as a dummy text generator and a Markdown parser.



Configuration
-------------

Configuration goes in a `twigexpress.json` file at the root of your project. This file should follow the JSON format ([here’s a good introduction](https://www.digitalocean.com/community/tutorials/an-introduction-to-json)).

See the [list of available options](doc/config.md).

You might want to use this file to define global variables, which will be available in all Twig templates in your project. This might look like this:

```json
{
  "global_vars": {
    "navItems": ["Home", "Services", "About"]
  }
}
```

This allows you to use the variables directly in any Twig template:

```twig
{% for item in navItems %}
  <a href="#">{{ item }}</a>
{% endfor %}
```


Library and license info
------------------------

[TwigExpress][] is licensed under the MIT License.

We are using the following libraries:

- [Twig library][TWIG_LIB] (BSD License)
- [Karwana\Mime][MIME_LIB] (MIT License)
- [Parsedown][] (MIT License)
- [php-loremipsum][] (MIT License)
- [highlight.js][] (BSD License)


[TwigExpress]: https://github.com/gradientz/twig-express
[DOWNLOAD]: https://github.com/gradientz/twig-express/archive/download.zip
[TWIG_HOME]: https://twig.sensiolabs.org/
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[TWIG_INTRO]: https://twig.sensiolabs.org/doc/1.x/templates.html
[Parsedown]: http://parsedown.org/
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[highlight.js]: https://github.com/isagalaev/highlight.js
