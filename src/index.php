<?php

if (PHP_SAPI === 'cli') {
    $msg = <<<EOL
TwigExpress version: 2.1.2
Use TwigExpress with a HTTP server. For example:
    php --server localhost:8000 twigexpress.phar
EOL;
    echo $msg . PHP_EOL;
    exit;
}

require_once __DIR__ . '/lib/Mime/Mime.php';
require_once __DIR__ . '/lib/LoremIpsum/LoremIpsum.php';
require_once __DIR__ . '/lib/Parsedown/Parsedown.php';
require_once __DIR__ . '/lib/Twig/Autoloader.php';
require_once __DIR__ . '/Controller.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/TwigEnv.php';

$controller = new TwigExpress\Controller();
$text = $controller->output();
if (is_string($text)) echo $text;
