# MIME #

[![Build Status](https://travis-ci.org/karwana/php-mime.svg?branch=master)](https://travis-ci.org/karwana/php-mime)

MIME type and file extension utilities for PHP. Powered by [`finfo`](http://php.net/manual/en/book.fileinfo.php) and the Apache-provided public domain [mime.types](https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types) map of media types to unique file extension(s).

## Examples ##

To get started, use the namespace wherever you want to use this library.

```php
use Karwana\Mime;
```

### For uploaded files ###

First we get the canonical extension and use it for the permanent file name. The original file name is used before falling back to running `finfo` if the file has no extension or if the extension is unlisted.

```php
$extension = Mime::guessExtension($_FILES['my_file']['tmp_name'], $_FILES['my_file']['name']);

move_uploaded_file($_FILES['my_file']['tmp_name'], 'uploads/' . Uuid::v4() . '.' . $extension);
```

Later on, if we want to serve the file to the client, we can return the appropriate MIME type.

```php
header('Content-Type: ' . Mime::guessType($my_file));
header('Content-Length: ' . filesize($my_file));
readfile($my_file);
```

### Add an extension to an extensionless file ###

You might want to do this as part of a batch job.

```php
$my_file = 'path/to/extensionless_file';

rename($my_file, $my_file . '.' . Mime::guessExtension($my_file));
```

## Development ##

Run tests using `$ vendor/bin/phing test`.

Use the provided script to update the MIME type list to the latest version from Apache's tracker.

```bash
curl https://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types | \
bin/mime_types2json > Mime/Resources/mime_types.json
```

## License ##

See `LICENSE`.
