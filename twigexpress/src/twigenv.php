<?php

require_once __DIR__ . '/../lib/Twig/Autoloader.php';
require_once __DIR__ . '/../lib/Parsedown/Parsedown.php';
require_once __DIR__ . '/../lib/LoremIpsum/LoremIpsum.php';

Twig_Autoloader::register();

use joshtronic\LoremIpsum;
//use Parsedown;
//use Twig_Environment;
//use Twig_Extension_Debug;
//use Twig_Loader_Filesystem;
//use Twig_SimpleFunction;


// -----------------------------
// Twig settings and global vars
// -----------------------------

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


// ----------------------------
// Prepare the Twig environment
// ----------------------------

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
$twigEnv->addGlobal('_base', BASE_URL);

// Add the global variables from the config
if (is_array($userConfig) && array_key_exists('globals', $userConfig) && is_array($userConfig['globals'])) {
    foreach ($userConfig['globals'] as $key => $value){
        $twigEnv->addGlobal($key, $value);
    }
}

// Twig function for generating fake latin text
// Usage: 
//   {{ lorem('5 words') }}
//   {% for paragraph in lorem('[10p]') %}
//     <p>{{ paragraph }}</p>
//   {% endfor %}
$twigEnv->addFunction(new Twig_SimpleFunction('lorem', 'makeLoremIpsum'));

// Twig filter that transforms a string with Parsedown
// Usage:
//   {{ markdown(someText) }}
//   {{ markdown(someText, inline=true) }}
$twigEnv->addFunction(new Twig_SimpleFunction('markdown', 'processMarkdown'));

// Twig function that lists files for one or several glob patterns
// Usage:
//   {% set files = files('*.*') %}
//   {% set files = files('*.*', 'starting-folder') %}
$twigEnv->addFunction(new Twig_SimpleFunction('files', function($patterns='*', $where='') {
    $root = rtrim(ROOT_DIR . '/' . $where, '/');
    return getFileNames($patterns, $root, 'file');
}));

// Twig function that lists folders for one or several glob patterns
// Usage:
//   {% set folders = folders('*.*') %}
//   {% set folders = folders('*.*', 'starting-folder') %}
$twigEnv->addFunction(new Twig_SimpleFunction('folders', function($patterns='*', $where='') {
    $root = rtrim(ROOT_DIR . '/' . $where, '/');
    return getFileNames($patterns, $root, 'dir');
}));


/**
 * Transform a string with Parsedown
 * @param string  $text   Markdown text to process
 * @param boolean $inline Do not output paragraph-level tags
 * @return string
 */
function processMarkdown($text, $inline=false) {
    // We might end up with Twig objects in some cases
    $value = (string) $text;
    if ($inline) return Parsedown::instance()->line($value);
    else return Parsedown::instance()->text($value);
}


/**
 * Generate fake latin text using joshtronic\LoremIpsum
 *
 * Syntax for command string is:
 *     'min-max type'   -> returns a string
 *     '[min-max type]' -> returns an array
 *
 * Available types:
 * - 'words' (synonyms: 'word', 'w')
 * - 'sentences' (synonyms: 'sentence', 's')
 * - 'paragraphs' (synonyms: 'paragraph', 'p')
 *
 * @param string $command Count and type of content to generate
 * @return array|string
 */
function makeLoremIpsum($command='1-7w') {
    if (!is_string($command)) return '';
    if (!preg_match('/^\[?\s*(\d{1,3})(-\d{1,3})?\s*([a-z]{1,10})\s*\]?$/', strtolower(trim($command)), $matches)) {
        return '';
    }
    if ($matches[2]) {
        $min = (int) $matches[1];
        $max = (int) substr($matches[2], 1);
        $count = $min <= $max ? rand($min, $max) : rand($max, $min);
    } else {
        $count = (int) $matches[1];
    }
    $method = 'words';
    switch ($matches[3]) {
        case 'w': case 'word': case 'words':
            $method = 'words'; break;
        case 's': case 'sentence': case 'sentences':
            $method = 'sentences'; break;
        case 'p': case 'paragraph': case 'paragraphs':
            $method = 'paragraphs'; break;
    }
    $method .= strpos($matches[0], '[') === 0 ? 'Array' : '';

    // Prepare the generator, calling it once to call the private shuffle
    // method (and avoid getting 'lorem ipsum' every time).
    $generator = new LoremIpsum();
    $generator->word();

    if (method_exists($generator, $method)) {
        $args = array_merge([$count], array_slice(func_get_args(), 1));
        return call_user_func_array( [$generator, $method], $args );
    }
    return '';
}
