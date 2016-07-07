<?php

require_once __DIR__ . '/../lib/Mime/Mime.php';

use Karwana\Mime\Mime;


/**
 * Check that files exist either directly as-is or as a Twig template
 * @param string $path File path
 * @param string $root Root directory
 * @return array
 */
function getFileInfo($path, $root) {
    $info = array(
        'file' => NULL,
        'twig' => NULL,
        'type' => 'text/html'
    );
    if (!is_string($path) || $path === '') {
        return $info;
    }

    // One does not simply moonwalk into Mordor
    $path = str_replace('..', '', trim($path));

    // Figure out mime type from extension
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext && $type = Mime::getTypeForExtension($ext)) {
        $info['type'] = $type;
    }

    // Allows loading index.html or index.twig
    $isFolder = substr($path, -1) === '/';
    $path = ltrim($path, '/');
    $filePath = $path . ($isFolder ? 'index.html' : '');
    $twigPath = $path . ($isFolder ? 'index' : '') . '.twig';

    // Figure out if we're looking for a static file or template
    if (is_file("$root/$filePath")) {
        $info['file'] = $filePath;
    }
    else if (is_file("$root/$twigPath")) {
        $info['twig'] = $twigPath;
    }

    return $info;
}

/**
 * Lists files and folders for one or several glob patterns
 * (not recursive, and starting from the provided root).
 * @param string|array $patterns Glob pattern(s) of files or folders to find
 * @param string $root Root folder to look from
 * @param string $type Type of element to return: 'folder', 'file' or both
 * @return array
*/
function getFileNames($patterns='*', $root=null, $type=null) {
    if (!is_string($root)) return [];
    if (!is_array($patterns)) $patterns = [$patterns];
    $files = [];
    // Find files to include and exclude
    foreach($patterns as $p) {
        $p = is_string($p) ? ltrim($p, '\\/') : '';
        if ($p == '' || strpos($p,'..') !== false) continue;
        $files = array_merge($files, glob("$root/$p", GLOB_BRACE));
    }
    // Filter results
    if ($type == 'file') $files = array_filter($files, 'is_file');
    if ($type == 'dir')  $files = array_filter($files, 'is_dir');
    // Clean up results
    $result = array_map(function($file) use ($root) {
        $path = str_replace('\\','/', $file);
        $path = str_replace($root . '/', '', $path);
        return rtrim($path, '/');
    }, $files);
    // Sort alphabetically
    sort($result);
    return $result;
}

/**
 * Render an info or error page and stop script
 * @param int $code HTTP error code
 * @param array $data Variables for the error template
 */
function exitWithPage($code=null, $data=[]) {
    $defaults = [
        'title' => '',
        'subtitle' => '',
        'message' => '',
        'code' => '',
        'url' => REQUEST_PATH,
        'base' => BASE_URL
    ];
    $statuses = [
        '200' => 'OK',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '500' => 'Internal Server Error'
    ];
    if (is_int($code)) $code = (string) $code;
    if (!array_key_exists($code, $statuses)) $code = '404';
    header('HTTP/1.1 ' . $code . ' ' . $statuses[$code]);
    header('Content-Type:text/html;charset=utf-8');
    extract(array_merge($defaults, $data));
    require __DIR__ . '/../tpl/page.php';
    exit;
}

/**
 * Show an error page for a Twig_Error, with the faulty Twig code if we can.
 * @param Twig_Error $error
 * @param string $root Twig root
 */
function renderTwigError(Twig_Error $error, $root) {
    $data = [
        'title' => get_class($error),
        'subtitle' => $error->getTemplateFile(),
        'message' => $error->getMessage(),
        'code' => '',
        'isError' => true
    ];
    // Get a few lines of code from the buggy template
    $file = $root . '/' . $error->getTemplateFile();
    if (file_exists($file)) {
        $line = $error->getTemplateLine();
        $plus = 5;
        $code = file_get_contents($file);
        $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split("/(\r\n|\n|\r)/", $code);
        $start = max(1, $line - $plus);
        $limit = min(count($lines), $line + $plus);
        $excerpt = [];
        for ($i = $start - 1; $i < $limit; $i++) {
            $frag = '<span data-num="'.($i+1).'"></span>';
            if ($i === $line - 1) $frag .= "<mark>$lines[$i]</mark>";
            else $frag .= $lines[$i];
            $excerpt[] = $frag;
        }
        // Update error page info
        $data['subtitle'] = "Line $line in $file";
        $data['code'] = implode("\n", $excerpt);
        $data['message'] = $error->getRawMessage();
    }
    $data['url'] = REQUEST_PATH;
    $data['base'] = BASE_URL;
    exitWithPage(500, $data);
}

/**
 * Show a Twig file with syntax highlighting
 * @param string $path Full path to file
 */
function renderTwigSource($path) {
    $source = file_get_contents($path);
    $data = [
        'code' => htmlspecialchars($source, ENT_NOQUOTES, 'UTF-8'),
        'isError' => false
    ];
    exitWithPage(200, $data);
}
