<?php

$NAME = 'twigexpress.phar';
$SRC  = __DIR__ . '/src';

if (PHP_SAPI !== 'cli') {
    echo 'This script can only run on the CLI.' . PHP_EOL;
    exit();
}

if (file_exists($NAME)) {
    echo 'Removing existing ' . $NAME . PHP_EOL;
    Phar::unlinkArchive($NAME);
}

try {
    $phar = new Phar($NAME, 0, $NAME);

    // Add all files in the main directory
    $phar->buildFromDirectory($SRC);
    $phar->setStub( $phar->createDefaultStub('start.php', 'start.php') );

    // Should be alright now
    $count = $phar->count();

    // Composer project disabled this for interoperability,
    // should we do the same?
    $phar->compressFiles(Phar::GZ);

    echo "Built $NAME (from $count files)" . PHP_EOL;
}
catch (UnexpectedValueException $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
