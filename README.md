TwigExpress
===========

*[Download][DOWNLOAD]. Unzip. Write [Twig templates](#how-can-i-learn-twig) and see the result.*

TwigExpress packages the [Twig templating engine][TWIG_HOME], and a few other tools, in a single file. Our goal is to make it easy to get started with Twig if you’re a designer or a front-end developer, without having to install a big PHP framework or a CMS.

*Features*

- Renders Twig templates! (And shows useful information when things go wrong.)
- Lets you define global variables that all templates have access to.
- And a few extra tools: convert Markdown text to HTML, list files and folders…

*Documentation*

1. [# Installation](#installation)
2. [# Adding content](#adding-content)
3. [# Global variables](#global-variables)
4. [# More documentation](#more-documentation)
5. [# Library and license info](#library-and-license-info)


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


Adding content
--------------

After installing TwigExpress, you should have a folder whose content looks like this:

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


Global variables
----------------

You can add global variables that will be available in every template rendered by TwigExpress. Define them in the `twigexpress.json` file:

```json
{
    "globals": {
        "FACEBOOK_APP_ID": "542F159A0213",
        "someDefaultValue": false
    }
}
```

Then in every template you will be able to use those variables, e.g. like this:

```twig
Our Facebook App ID is: {{ 542F159A0213 }}

{% if someDefaultValue %}
    The "someDefaultValue" variable is true.
{% else %}
    The "someDefaultValue" variable is false.
{% endif %}
```

You can add as many global variables as you need. The values can be strings, numbers, arrays or objects, as long as they follow [the JSON format](https://en.wikipedia.org/wiki/JSON#Example).


More documentation
------------------

- Need to learn Twig? See [our short introduction to Twig](doc/twig-intro.md)
- [TwigExpress-specific features](doc/extra.md)
- [Advanced configuration](doc/config.md)


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
[TWIG_HOME]: http://twig.sensiolabs.org/
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[Parsedown]: http://parsedown.org/
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[highlight.js]: https://github.com/isagalaev/highlight.js
