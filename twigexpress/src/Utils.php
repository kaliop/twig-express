<?php

namespace Gradientz\TwigExpress;

use Karwana\Mime\Mime;

class Utils
{
    /**
     * Cleans up a local resource path, removing back-slashes, double dots, etc.
     * Should not be necessary for content from a URL but let's be on the safe side.
     * @param  string $path
     * @return string
     */
    static function getCleanPath($path)
    {
        return preg_replace(
            ['/\\\/', '/\/{2,}/', '/\.{2,}/'],
            ['/', '/', '.'],
            $path
        );
    }

    /**
     * Send HTTP headers with status code and content type
     * @param string $code - Status code
     * @param string $type - Content type, if known
     * @param string $filename - Will guess the content type from this, if provided
     */
    static function sendHeaders($code='200', $type='text/html', $filename='')
    {
        $statuses = [
            '200' => 'OK',
            '403' => 'Forbidden',
            '404' => 'Not Found',
            '500' => 'Internal Server Error'
        ];
        if (is_int($code)) $code = (string) $code;
        if (!array_key_exists($code, $statuses)) $code = '404';

        // Figure out content type, if not set
        if ($filename !== '') {
            $ext = null;
            if (substr($filename, -5) === '.twig') {
                $ext = pathinfo(substr($filename, 0, -5), PATHINFO_EXTENSION);
            } else {
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
            }
            if ($ext && $guess = Mime::getTypeForExtension($ext)) {
                $type = $guess;
            }
        }

        // Header should be correct for the intended display mode,
        // e.g. text/html for a 404 page even if the URL asked for a `.json`,
        // so we can send it right away.
        $typeHeader = 'Content-Type: '.$type;
        if (preg_match('/(text|xml|svg|javascript|json)/', $type)) {
            $typeHeader .= ';charset=utf-8';
        }
        header('HTTP/1.1 '.$code.' '.$statuses[$code]);
        header($typeHeader);
    }

    /**
     * Make an associative array with URL=>Name values
     * representing the breadcrumbs for a given base URL and path
     * @param  string $baseUrl (no trailing slash)
     * @param  string $siteName
     * @param  string $path
     * @param  boolean $twigExt - Make the '.twig' extension a separate crumb
     * @return array
     */
    static function makeBreadcrumbs($baseUrl, $siteName, $path, $twigExt=false)
    {
        $url = $baseUrl . '/';
        $crumbs = [['url' => $url, 'name' => $siteName]];
        $fragments = array_filter(explode('/', $path));
        $last = array_pop($fragments);

        foreach ($fragments as $fragment) {
            $url .= $fragment . '/';
            $crumbs[] = ['url' => $url, 'name' => $fragment];
        }
        if ($last) {
            $ext = pathinfo($last, PATHINFO_EXTENSION);
            if ($twigExt && $ext === 'twig') {
                $noTwigExt = substr($last, 0, -5);
                $crumbs[] = ['url' => $url . $noTwigExt, 'name' => $noTwigExt];
                $crumbs[] = ['url' => $url . $last, 'name' => '.twig'];
            }
            else {
                $url .= $last . ($ext === '' ? '/' : '');
                $crumbs[] = ['url' => $url, 'name' => $last];
            }
        }

        return $crumbs;
    }

    /**
     * Format a block of code (especially Twig code) for displaying in an HTML page.
     * @param string $code Source code
     * @param bool $numbers Add line numbers
     * @param int $highlight Line number to highlight
     * @param int $extract Number of lines to show before and after an highlighted line
     * @return string
     */
    static function formatCodeBlock($code, $numbers=true, $highlight=0, $extract=4)
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lines = preg_split("/(\r\n|\n)/", $escaped);
        // Use 1-indexes
        $start = 1;
        $end = count($lines);
        if ($highlight > 0) {
            $highlight = min($end, $highlight);
            $start = max(1, $highlight - $extract);
            $end = min($end, $highlight + $extract);
        }
        $excerpt = [];
        // Add line numbers and mark the selected line
        for ($i = $start - 1; $i < $end; $i++) {
            $text = $lines[$i];
            $num = '';
            // Don't show number on a last empty line
            if ($numbers && ($i < $end - 1 || $text !== '')) {
                $num = '<span data-num="'.($i+1).'"></span>';
            }
            if ($i === $highlight - 1) {
                $excerpt[] = "$num<mark>$text</mark>";
            } else {
                $excerpt[] = $num . $text;
            }
        }
        return implode("\n", $excerpt);
    }

    /**
     * Map a Twig template’s filename with a syntax highlighting name
     * used by Highlight.js.
     * @param string $filename
     * @return string
     */
    static function getHighlightLanguage($filename)
    {
        // Try to figure out the subLanguage
        $subLang   = 'xml';
        $subLangs  = [
            'xml'  => 'xml',
            'html' => 'xml',
            'htm'  => 'xml',
            'json' => 'json',
            'js'   => 'javascript',
            'css'  => 'css',
            'md'   => 'markdown',
            'mdown' => 'markdown',
            'markdown' => 'markdown'
        ];
        $ext = pathinfo(preg_replace('/\.twig$/', '', strtolower($filename)), PATHINFO_EXTENSION);
        if (array_key_exists($ext, $subLangs)) {
            $subLang = $subLangs[$ext];
        }
        return $subLang;
    }

    /**
     * Lists files and folders for one or several glob patterns
     * (not recursive, and starting from the provided root).
     * @param string|array $patterns Glob pattern(s) of files or folders to find
     * @param string $where Folder to look from
     * @param string $type Type of element to return: 'folder', 'file' or both
     * @return array
     */
    static function glob($patterns='*', $where=null, $type=null)
    {
        if (is_string($patterns)) $patterns = [$patterns];
        $files = [];
        // Find files to include and exclude
        foreach($patterns as $p) {
            $p = is_string($p) ? ltrim($p, '\\/') : '';
            if ($p == '' || strpos($p,'..') !== false) continue;
            $files = array_merge($files, glob("$where/$p", GLOB_BRACE));
        }
        // Filter results
        if ($type == 'file') $files = array_filter($files, 'is_file');
        if ($type == 'dir')  $files = array_filter($files, 'is_dir');
        // Clean up results
        $result = array_map(function($file) use ($where) {
            $path = str_replace('\\','/', $file);
            $path = str_replace($where . '/', '', $path);
            return rtrim($path, '/');
        }, $files);

        // Sort alphabetically
        sort($result);
        return $result;
    }
}
