Building TwigExpress
====================

## Minify CSS and JS

```
$ cd dev
$ npm install
$ npm run build
```

## Making the PHAR

The command should already be included in `npm run build`, but you can run manually:

```
php makephar.php
```

By default, PHP installations disable Phar creation, so you have to locate your `php.ini` with:

```
php --ini
```

And set `phar.readonly = Off`.

## Updating the highlight-main.js file

Mostly to keep up with updates, so no need to do that every time.

```
$ git clone https://github.com/isagalaev/highlight.js.git
$ cd highlight.js && npm install
$ node tools/build.js -n css xml javascript json markdown twig
$ cd ..
$ cp highlight.js/build/highlight.pack.js js/1-highlight.js
$ rm -rf highlight.js
```
