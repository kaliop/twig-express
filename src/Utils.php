<?php

namespace TwigExpress;

use Karwana\Mime\Mime;
use joshtronic\LoremIpsum;
use Parsedown;

class Utils
{
    /** @var Parsedown */
    static private $markdown;

    /** @var LoremIpsum */
    static private $lorem;

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
        header("HTTP/1.1 $code $statuses[$code]");
        header($typeHeader);
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
     * @param string $type Type of element to return: 'dir', 'file' or both
     * @return array
     */
    static function getFileList($patterns='*', $where=null, $type=null)
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

    /**
     * Retrieve a value from $_GET or $_POST, using a fallback value if
     * the queried key doesn't exist. Allows defining the method ('get',
     * 'post' or 'cookie') as a prefix in the name, e.g. 'post:somevar'.
     * @param string $name
     * @param string [$fallback]
     * @return mixed
     */
    static function getHttpParameter($name='', $fallback='')
    {
        $bag = $_GET;
        $key = (string) $name;
        $start = strtolower(explode(':', $key)[0]);
        if ($start === 'get') {
            $key = substr($key, 4);
        }
        if ($start === 'post') {
            $key = substr($key, 5);
            $bag = $_POST;
        }
        if ($start === 'cookie') {
            $key = substr($key, 7);
            $bag = $_COOKIE;
        }
        return array_key_exists($key, $bag) ? $bag[$key] : $fallback;
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
    static function makeLoremIpsum($command='1-7w')
    {
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

        if (static::$lorem === null) {
            // Prepare the generator, calling it once to call the private shuffle
            // method (and avoid getting 'lorem ipsum' every time).
            static::$lorem = new LoremIpsum();
            static::$lorem->word();
        }

        if (method_exists(static::$lorem, $method)) {
            $args = array_merge([$count], array_slice(func_get_args(), 1));
            $result = call_user_func_array( [static::$lorem, $method], $args );
            // Make sure we use a capital letter first, because LoremIpsum
            // doesn't do it for words. So we're more consistent, and users
            // who don't want that can still use the |lower Twig filter.
            if (is_string($result)) {
                return ucfirst($result);
            }
            if (is_array($result)) {
                return array_map('ucfirst', $result);
            }
        }
        return '';
    }

    /**
     * Transform a string with Parsedown
     * @param string  $text   Markdown text to process
     * @param boolean $inline Do not output paragraph-level tags
     * @return string
     */
    static function processMarkdown($text='', $inline=false)
    {
        // We might end up with Twig objects in some cases
        $value = (string) $text;
        if (static::$markdown === null) {
            static::$markdown = Parsedown::instance();
        }
        if ($inline) return static::$markdown->line($value);
        else return static::$markdown->text($value);
    }
}
