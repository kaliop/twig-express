<?php

use joshtronic\LoremIpsum;

define('ROOT_DIR', str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']));
define('REQUEST_PATH', explode('?', $_SERVER['REQUEST_URI'])[0]);

require_once __DIR__ . '/helpers.php';


// ----------------
// Get request info
// ----------------

$requestPath = REQUEST_PATH;
$baseUrl = '/';

// If we're on Apache in a subfolder, e.g. http://localhost/twigexpress/request-path,
// we need to substract the subfolder from the request path.
if (php_sapi_name() != 'cli-server') {
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
    $subFolder = str_replace($docRoot, '', ROOT_DIR);
    if (strlen($subFolder) > 1 && strpos($requestPath, $subFolder) === 0) {
        $requestPath = substr($requestPath, strlen($subFolder));
        $baseUrl = '/' . trim($subFolder, '/') . '/';
    }
}

$fileInfo = getFileInfo( $requestPath, ROOT_DIR );

// Should only happen with the CLI server
if ($fileInfo['file']) {
    if ($fileInfo['type']) header('Content-Type: ' . $fileInfo['type']);
    return readfile(ROOT_DIR . '/' . $fileInfo['file']);
}


// -------------------
// Render the template
// -------------------

else if ($fileInfo['twig']) {
    // Get the $twigEnv and dependencies
    require_once __DIR__ . '/twigenv.php';

    // All set? Render the template
    try {
        $body = $twigEnv->render( $fileInfo['twig'] );
        if ($fileInfo['type']) header('Content-Type: ' . $fileInfo['type']);
        echo $body;
    }
    catch (Twig_Error $e) {
        renderTwigError($e, ROOT_DIR);
    }
}


// --------------------------------
// Error page for 404 or disallowed
// --------------------------------

else {
    $path = $requestPath;
    if (substr($path, -1) == '/') $path .= 'index.twig';
    exitWithErrorPage(404, [
        'title' => 'File does not exist',
        'message' => 'Could not find: <code> ' . $path . '</code><br>' .
            'Looking in: <code>' . ROOT_DIR . '</code>'
    ]);
}
