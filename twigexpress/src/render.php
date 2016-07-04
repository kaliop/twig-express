<?php

// -----------------------
// Figure out the root dir
// -----------------------

$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_FILENAME']);
$scriptRoot = preg_replace('/\/twigexpress(\.phar|\/start\.php)$/', '', $scriptName);
$requestPath = explode('?', $_SERVER['REQUEST_URI'])[0];

// If we have a conventional folder structure for Apache, with a .htaccess
// and a twigexpress.phar at the root of the folder, try to guess if we’re
// in a subfolder, and correct the root dir, request path and base URL.
if (php_sapi_name() !== 'cli-server' && $scriptRoot !== $docRoot && file_exists($scriptRoot . '/.htaccess')) {
    $trimmed = $requestPath;
    $baseUrl = '/';
    $parts = array_filter(explode('/', $scriptRoot));
    // For a script root of '/a/b/c', try to remove '/c' at the start of the
    // request URI, then '/b/c', then '/a/b/c'. Latest match wins.
    for ($i=count($parts); $i > 0; $i--) {
        $pattern = '/' . implode('/', array_slice($parts, $i-1));
        $result = preg_replace('#^'. preg_quote($pattern) .'#', '', $requestPath);
        if ($result !== $requestPath) {
            $trimmed = $result;
            $baseUrl = $pattern . '/';
        }
    }
    define('ROOT_DIR', $scriptRoot);
    define('REQUEST_PATH', $trimmed);
    define('BASE_URL', $baseUrl);
}
// Simpler case: we trust the document root we have
else {
    define('ROOT_DIR', $docRoot);
    define('REQUEST_PATH', $requestPath);
    define('BASE_URL', '/');
}


// --------------------
// Serve or render file
// --------------------

require_once __DIR__ . '/helpers.php';

$fileInfo = getFileInfo( REQUEST_PATH, ROOT_DIR );

$typeHeader = 'Content-Type: '.$fileInfo['type'];
if (preg_match('/(text|xml|svg|javascript|json)/', $fileInfo['type'])) {
    $typeHeader .= ';charset=utf-8';
}

if ($fileInfo['file']) {
    $file = ROOT_DIR . '/' . $fileInfo['file'];
    header($typeHeader);
    // Serve Twig file as a HTML page with syntax highlighting
    if (pathinfo($file, PATHINFO_EXTENSION) === 'twig') {
        renderTwigSource($file, REQUEST_PATH, BASE_URL);
    }
    // Serve static file directly (for the PHP CLI server)
    else {
        return readfile($file);
    }
}
else if ($fileInfo['twig']) {
    // Get the $twigEnv and dependencies
    require_once __DIR__ . '/twigenv.php';

    // All set? Render the template
    try {
        $body = $twigEnv->render( $fileInfo['twig'] );
        header($typeHeader);
        echo $body;
    }
    catch (Twig_Error $e) {
        renderTwigError($e, ROOT_DIR);
    }
}
else {
    // Error page for 404 or disallowed
    $path = REQUEST_PATH;
    if (substr($path, -1) == '/') $path .= 'index.twig';
    exitWithPage(404, [
        'title' => 'File does not exist',
        'message' => 'Could not find&hairsp;: <code class="error"> ' . $path . '</code><br>' .
            'Document root&hairsp;: <code>' . ROOT_DIR . '</code>'
    ]);
}
