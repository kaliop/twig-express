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
    // Or ask directly for a Twig file
    $path = str_ireplace('.twig', '', $path);
    // Figure out mime type from extension (including the one that comes before .twig, if any)
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext) $info['type'] = Mime::getTypeForExtension($ext);

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
    return array_map(function($file) use ($root) {
        $path = str_replace('\\','/', $file);
        $path = str_replace($root . '/', '', $path);
        return rtrim($path, '/');
    }, $files);
}

/**
 * Render a message and stop script
 * @param int $code HTTP error code
 * @param array $data Variables for the error template
 */
function exitWithErrorPage($code=404, $data=[]) {
    $defaults = [
        'title' => 'No page found at this URL',
        'message' => '',
        'file' => '',
        'code' => ''
    ];
    $statuses = [
        403 => '403 Forbidden',
        404 => '404 Not Found',
        500 => '500 Internal Server Error'
    ];
    extract(array_merge($defaults, $data));
    header('HTTP/1.1 ' . $statuses[$code]);
    header('Content-Type:text/html;charset=utf-8');
    require __DIR__ . '/errorpage.php';
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
        'message' => $error->getMessage(),
        'file' => $error->getTemplateFile(),
        'code' => ''
    ];
    // Get a few lines of code from the buggy template
    $file = $root . '/' . $error->getTemplateFile();
    if (file_exists($file)) {
        $line = $error->getTemplateLine();
        $plus = 4;
        $code = file_get_contents($file);
        $code = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split("/(\r\n|\n|\r)/", $code);
        $start = max(1, $line - $plus);
        $limit = min(count($lines), $line + $plus);
        $excerpt = [];
        for ($i = $start - 1; $i < $limit; $i++) {
            $attr = 'data-line="'.($i+1).'"';
            if ($i === $line - 1) $excerpt[] = "<mark $attr>$lines[$i]</mark>";
            else $excerpt[] = "<span $attr>$lines[$i]</span>";
        }
        // Update error page info
        $data['file'] = $file . ':' . $line;
        $data['code'] = implode("\n", $excerpt);
        $data['message'] = $error->getRawMessage();
    }
    exitWithErrorPage(500, $data);
}
