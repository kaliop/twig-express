TwigExpress
===========

TwigExpress packages the [Twig templating engine][TWIG_HOME], and a few other tools, in a single file.

Our goal is to make it easy to get started with Twig if you’re a designer or a front-end developer, without having to install a heavy PHP framework or CMS.

Main features:

-   Browse and serve files (⚠ only for development!)
-   Render Twig templates
-   Error pages with code excerpt, source view
-   And a few extra tools for prototyping (dummy text, Markdown to HTML…):

Table of contents
-----------------

1.  [Running TwigExpress](#running-twigexpress)
2.  [Adding content](#adding-content)
3.  [Writing Twig templates](#writing-twig-templates)<br>
    ↪ [Getting Started with Twig][DOC_INTRO]<br>
    ↪ [TwigExpress-specific features][DOC_EXTRAS]
4.  [Configuration](#configuration)<br>
    ↪ [TwigExpress configuration reference][DOC_CONFIG]<br>
    ↪ [Using TwigExpress with Apache][DOC_APACHE]
5.  [Library and license info](#library-and-license-info)

Running TwigExpress
-------------------

### Requirements

PHP 5.4+ available on the command line.

-   On macOS, you should have PHP installed already.
-   On Windows, one easy way to install PHP (and other tools) is [XAMPP](https://www.apachefriends.org/download.html).

### Download TwigExpress

-   [Download a ZIP of this repo][DOWNLOAD] and unzip.
-   Recommended: rename `twig-express-master` to your project’s name.

### Usage

Open your project folder in a Terminal or Command Prompt and run this command:

```sh
php --server localhost:8000 twigexpress.phar
```

Now load [http://localhost:8000/](http://localhost:8000/) in a web browser to browse your files.

Any file ending in `.twig` will be interpreted as a Twig template. You can check out the example pages in the `demo` directory.

Adding content
--------------

After installing TwigExpress, you should have a folder whose content looks like this:

```
myproject/
    demo/
    LICENSE
    README.md
    twigexpress.json
    twigexpress.phar
```

If you don’t care about the demo and read-me, you can keep these only:

```
myproject/
    twigexpress.json
    twigexpress.phar
```

You can add your own content anywhere. This content can be Twig templates (which must have the `.twig` extension), CSS, scripts, images, etc.

<table>
  <tr>
    <th scope="col">Example file path</th>
    <th scope="col">Corresponding URL</th>
  </tr>
  <tr>
    <td><code>myproject/index.twig</code></td>
    <td><code>http://localhost:8000/</code></td>
  </tr>
  <tr>
    <td><code>myproject/some/page.twig</code></td>
    <td><code>http://localhost:8000/some/page</code></td>
  </tr>
  <tr>
    <td><code>myproject/css/styles.css</code></td>
    <td><code>http://localhost:8000/css/styles.css</code></td>
  </tr>
</table>

Writing Twig templates
----------------------

There are two sets of features available in templates:

-   **Syntax and features native to the Twig language**<br>
    See our short guide [“Getting started with Twig”][DOC_INTRO], and the official [“Twig for Template Designers”][TWIG_INTRO] guide.

-   **Features specific to TwigExpress**<br>
    We’re added [a few extra features][DOC_EXTRAS] to help you write HTML prototypes, such as a dummy text generator and a Markdown parser.

Configuration
-------------

Configuration goes in a `twigexpress.json` file at the root of your project. This file should follow the JSON format ([here’s a good introduction](https://www.digitalocean.com/community/tutorials/an-introduction-to-json)).

More information:

- [TwigExpress configuration reference][DOC_CONFIG]
- [Using TwigExpress with Apache][DOC_APACHE]

Library and license info
------------------------

[TwigExpress][] is licensed under the MIT License.

We are using the following libraries:

- [Twig library][TWIG_LIB] (BSD License)
- [Karwana\Mime][MIME_LIB] (MIT License)
- [Parsedown][] (MIT License)
- [php-loremipsum][] (MIT License)
- [highlight.js][] (BSD License)


[TwigExpress]: https://github.com/kaliop/twig-express
[DOWNLOAD]: https://github.com/kaliop/twig-express/archive/master.zip
[DOC_INTRO]: https://github.com/kaliop/twig-express/blob/master/doc/intro.md
[DOC_EXTRAS]: https://github.com/kaliop/twig-express/blob/master/doc/extras.md
[DOC_CONFIG]: https://github.com/kaliop/twig-express/blob/master/doc/config.md
[DOC_APACHE]: https://github.com/kaliop/twig-express/blob/master/doc/apache.md

[TWIG_HOME]: https://twig.sensiolabs.org/
[TWIG_LIB]: https://github.com/twigphp/Twig
[MIME_LIB]: https://github.com/karwana/php-mime
[TWIG_INTRO]: https://twig.sensiolabs.org/doc/1.x/templates.html
[Parsedown]: http://parsedown.org/
[php-loremipsum]: https://github.com/joshtronic/php-loremipsum/
[highlight.js]: https://github.com/isagalaev/highlight.js
