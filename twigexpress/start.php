<?php

if (PHP_SAPI === 'cli') {
    $msg = <<<EOL
TwigExpress must be called by a HTTP server, such as Apache or PHP's dev server.
To use PHP's built-in server with TwigExpress, try:

    php -S localhost:8000 twigexpress.phar

from the directory that you want to serve.
EOL;
    echo $msg . PHP_EOL;
    exit();
}

require_once __DIR__ . '/lib/Mime/Mime.php';
require_once __DIR__ . '/src/Controller.php';
require_once __DIR__ . '/src/Utils.php';
require_once __DIR__ . '/src/TwigEnv.php';

$controller = new Gradientz\TwigExpress\Controller();
$text = $controller->output();
if (is_string($text)) echo $text;
