Using TwigExpress with Apache
=============================

As a reminder, instead of using a web server such as Apache you can run TwigExpress using PHP on the command line:

```sh
$ php --server localhost:8000 twigexpress.phar
```

But if you do want to use Apache, you can:

1. Put `twigexpress.phar` in your web root.
2. Create a `.htaccess` file with this content:

```apache
## Multiviews can create issues with filename.ext.twig
## patterns (when accessed as filename.ext).
Options -Indexes -Multiviews

## Treat .phar as PHP
AddHandler application/x-httpd-php .phar

# Serve all requests with TwigExpress
<IfModule mod_rewrite.c>
  RewriteEngine on
  RewriteBase /
  # pass-through if another rewrite rule has been applied already
  # (simpler with Apache 2.3+, use a [L,END] flag on the RewriteRule)
  RewriteCond %{ENV:REDIRECT_STATUS} 200
  RewriteRule ^ - [L]
  # avoid redirection loops
  RewriteCond %{REQUEST_URI} !twigexpress\.phar
  RewriteRule (.*) twigexpress.phar/index.php [L]
</IfModule>
```

Reminder: TwigExpress is not made for production sites, only for local development. Using it on a live server is a security risk.
