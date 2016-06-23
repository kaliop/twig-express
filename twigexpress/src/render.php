<?php

use Karwana\Mime\Mime;
use joshtronic\LoremIpsum;

define('ROOT_DIR', str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']));
define('REQUEST_PATH', explode('?', $_SERVER['REQUEST_URI'])[0]);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../lib/Mime/Mime.php';


// --------
// Settings
// --------

// Twig config
$twigConfig = [
    'debug' => true,
    'cache' => false,
    'autoescape' => true,
    'strict_variables' => true,
    'charset' => 'utf-8'
];

// Load user config
$userConfig = null;

if (file_exists($configFile = ROOT_DIR . '/twigexpress.json')) {
    $userConfigData = file_get_contents($configFile);
    $userConfig = json_decode($userConfigData, true);
    if ($jsonError = json_last_error()) {
        exitWithErrorPage(404, [
            'title' => json_last_error_msg(),
            'file' => $configFile,
            'message' => 'There was a problem while parsing your JSON config. ' .
                'JSON syntax is rather restrictive, so make sure there’s no syntax error. ' .
                '<a target="_blank" href="http://jsonlint.com/?json=' . rawurlencode($userConfigData) .
                '">Test it online with JSONLint</a>'
        ]);
    }
}

// Merge configs
if (is_array($userConfig)) foreach($twigConfig as $key=>$val) {
    if (array_key_exists($key, $userConfig)) $twigConfig[$key] = $userConfig[$key];
}


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
    require_once __DIR__ . '/../lib/Twig/Autoloader.php';
    require_once __DIR__ . '/../lib/LoremIpsum/LoremIpsum.php';
    require_once __DIR__ . '/../lib/Parsedown/Parsedown.php';
    Twig_Autoloader::register();

    $twigEnv = new Twig_Environment(
        new Twig_Loader_Filesystem(ROOT_DIR),
        $twigConfig
    );

    // Enable the 'dump' function
    $twigEnv->addExtension(new Twig_Extension_Debug());

    // Add get, post and cookie info
    $twigEnv->addGlobal('_get', $_GET);
    $twigEnv->addGlobal('_post', $_POST);
    $twigEnv->addGlobal('_cookie', $_COOKIE);
    $twigEnv->addGlobal('_base', $baseUrl);

    // Add the global variables from the config
    if (is_array($userConfig) && array_key_exists('globals', $userConfig) && is_array($userConfig['globals'])) {
        foreach ($userConfig['globals'] as $key => $value){
            $twigEnv->addGlobal($key, $value);
        }
    }

    /**
     * Twig function for using LoremIpsum, so that we can generate fake
     * latin words, sentences and paragraphs. We want a single instance
     * or each use will start with 'lorem ipsum' (a strange design choice).
     */
    $loremGenerator = new LoremIpsum();
    $twigEnv->addFunction(new Twig_SimpleFunction('lorem', function($command='1 word') use ($loremGenerator) {
        return makeLoremContent($loremGenerator, $command);
    }));

    /**
     * Twig filter that transforms a string with Parsedown
     * Usage:
     *     {{ markdown(someText) }}
     *     {{ markdown(someText, inline=true) }}
     *
     * @param string  $text   Markdown text to process
     * @param boolean $inline Do not output paragraph-level tags
     * @return string
     */
    $twigEnv->addFunction(new Twig_SimpleFunction('markdown', function($text, $inline=false) {
        $value = (string) $text;
        if ($inline) return Parsedown::instance()->line($value);
        else return Parsedown::instance()->text($value);
    }));

    /**
     * Twig function that lists files for one or several glob patterns
     * @param string|array $patterns Glob pattern(s) of files to find
     * @param string $where Optional root dir (relative from document root)
     * @return array
     */
    $twigEnv->addFunction(new Twig_SimpleFunction('files', function($patterns='*', $where='') {
        $root = rtrim(ROOT_DIR . '/' . $where, '/');
        return getFileNames($patterns, $root, 'file');
    }));

    /**
     * Twig function that lists folders for one or several glob patterns
     * @param string|array $patterns Glob pattern(s) of files to find
     * @param string $where Optional root dir (relative from document root)
     * @return array
     */
    $twigEnv->addFunction(new Twig_SimpleFunction('folders', function($patterns='*', $where='') {
        $root = rtrim(ROOT_DIR . '/' . $where, '/');
        return getFileNames($patterns, $root, 'dir');
    }));

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
